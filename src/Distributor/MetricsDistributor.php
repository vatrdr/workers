<?php declare(strict_types=1);

namespace Workers\Distributor;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Vatradar\Dataobjects\Vatsim\Pilot;
use Vatradar\Dataobjects\Vatsim\VatsimData;
use Vatradar\Dataobjects\DTO\Generic;
use Workers\Exchange;
use Workers\Factory\LoggerFactory;
use Workers\RoutingKey;
use Workers\WorkerInterface;
use function array_key_exists;
use function compact;
use function get_class;
use function preg_replace;
use function React\Async\coroutine;
use function React\Promise\all;
use function serialize;
use function unserialize;
use function Vatradar\basename;

class MetricsDistributor implements WorkerInterface
{
    protected LoggerInterface $log;

    public function __construct() {
        $this->log   = LoggerFactory::create(basename(get_class($this)));
    }

    /**
     * @param array<Pilot> $data
     * @return array
     */
    public function pilots(array $data): array {
        $total             = 0;
        $withoutFlightplan = 0;
        $withFlightplan    = 0;
        $ifr               = 0;
        $vfr               = 0;
        $stationary        = 0;
        $moving            = 0;

        $aircraft = [
            'unknown' => 0
        ];

        foreach($data as $pilot) {
            $total++;

            if($pilot->flightPlan) {
                $withFlightplan++;

                $short = $pilot->flightPlan->aircraftShort ?? 'unknown';

                if(array_key_exists($short, $aircraft)) { $aircraft[$short]++; } else { $aircraft[$short] = 1; }

                switch($pilot->flightPlan->flightRules) {
                    case 'I':
                        $ifr++;
                        break;

                    case 'V':
                        $vfr++;
                        break;
                }
            } else {
                $withoutFlightplan++;
                $vfr++;
                /** @var array<string, int> $aircraft */
                $aircraft['unknown']++;
            }

            if($pilot->groundspeed < 1) {
                $stationary++;
            } else {
                $moving++;
            }
        }

        return [
            'total' => $total,
            'withFlightPlan' => $withFlightplan,
            'withoutFlightPlan' => $withoutFlightplan,
            'ifr' => $ifr,
            'vfr' => $vfr,
            'stationary' => $stationary,
            'moving' => $moving,
            'aircraft' => $aircraft
        ];
    }

    /**
     * @param array<Pilot> $data
     * @return array
     */
    public function airports(array $data): array {
        $departures = [
            'ZZZZ' => 0
        ];
        $arrivals   = [
            'ZZZZ' => 0
        ];

        foreach($data as $pilot) {
            if(!$pilot->flightPlan) {
                $departures['ZZZZ']++;
                $arrivals['ZZZZ']++;
                continue;
            }

            $fp         = $pilot->flightPlan;
            $depAirport = (string) preg_replace('/[^[:alnum:]]/', '', $fp->departure);
            $arrAirport = (string) preg_replace('/[^[:alnum:]]/', '', $fp->arrival);
            if($depAirport === '') { $depAirport = 'ZZZZ'; }
            if($arrAirport === '') { $depAirport = 'ZZZZ'; }

            if(array_key_exists($depAirport, $departures)) { $departures[$depAirport]++; } else { $departures[$depAirport] = 1; }
            if(array_key_exists($arrAirport, $arrivals)) { $arrivals[$arrAirport]++; } else { $arrivals[$arrAirport] = 1; }

        }

        return compact('departures', 'arrivals');
    }

    /**
     * @param array<Pilot>  $data
     * @param string $ts
     * @return PromiseInterface
     */
    protected function process(array $data, string $ts): PromiseInterface {
        $this->log->debug('Process', ['vatsimTs' => $ts]);

        return coroutine(function() use ($data, $ts): Generic {
            $metrics = [];
            $metrics['timestamp'] = $ts;
            $metrics['pilot'] = $this->pilots($data);
            $metrics['airport'] = $this->airports($data);

            return new Generic(['type' => 'metrics'], $metrics, $ts);
        });

    }

    public function work(Channel $channel, Message $message, Client $client): PromiseInterface {
        /** @var VatsimData $data */
        $data = unserialize($message->content, ['allowed_classes' => true]);

        $promise = $this->process($data->pilots, $data->general->updateTimestamp->format('U'));

        $promise->then(function(Generic $results) use ($channel): PromiseInterface {
            $promises = [];
            $promises[] = $this->publish($channel, $results, Exchange::VATSIM_DISTRIBUTOR->value, RoutingKey::REDIS_METRICS->value);
            $promises[] = $this->publish($channel, $results, Exchange::VATSIM_DISTRIBUTOR->value, RoutingKey::INFLUX_METRICS->value);

            return all($promises);
        });

        return $promise;
    }

    protected function publish(Channel $channel, mixed $message, string $exchange, string $routingKey): PromiseInterface {
        $this->log->debug('Publish', compact('exchange', 'routingKey'));
        return $channel->publish(serialize($message), [], $exchange, $routingKey);
    }

}
