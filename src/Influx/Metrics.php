<?php

declare(strict_types=1);

namespace Workers\Influx;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Vatradar\Dataobjects\DTO\Influx;
use Workers\Exchange;
use Workers\Factory\LoggerFactory;
use Workers\RoutingKey;
use Workers\WorkerInterface;
use function array_key_exists;
use function compact;
use function get_class;
use function serialize;
use function unserialize;
use function Vatradar\basename;

class Metrics implements WorkerInterface
{
    protected LoggerInterface $log;

    public function __construct()
    {
        $this->log = LoggerFactory::create(basename(get_class($this)));
    }

    protected function process(array $metrics, string $ts): Influx
    {
        $points = [];

        $this->log->debug('Process', ['vatsimTs' => $ts]);

        if (array_key_exists('pilot', $metrics)) {
            $pilot = $metrics['pilot'];

            $points[] = Point::measurement('pilotmetrics')
                ->addField('totalPilots', $pilot['total'])
                ->addField('withFlightplan', $pilot['withFlightPlan'])
                ->addField('withoutFlightplan', $pilot['withoutFlightPlan'])
                ->addField('ifr', $pilot['ifr'])
                ->addField('vfr', $pilot['vfr'])
                ->addField('stationary', $pilot['stationary'])
                ->addField('moving', $pilot['moving'])
                ->time($ts, WritePrecision::S);

            foreach ($pilot['aircraft'] as $type => $amount) {
                $points[] = Point::measurement('aircraftcount')
                    ->addTag('type', $type)
                    ->addField('count', $amount)
                    ->time($ts, WritePrecision::S);
            }
        }

        if (array_key_exists('airport', $metrics)) {
            foreach (['departures', 'arrivals'] as $direction) {
                foreach ($metrics['airport'][$direction] as $airport => $count) {
                    $points[] = Point::measurement('airport_counts_' . $direction)
                        ->addTag('name', $airport)
                        ->addField('count', $count)
                        ->time($ts, WritePrecision::S);
                }
            }
        }

        return new Influx($points, $ts, 'vatsim_metrics');
    }

    public function work(Channel $channel, Message $message, Client $client): PromiseInterface
    {
        /** @var Influx $data */
        $data = unserialize($message->content, ['allowed_classes' => true]);

        $dto = $this->process($data->getData(), $data->getTimestamp());

        $exchange = Exchange::VATSIM_DISTRIBUTOR->value;
        $routingKey = RoutingKey::INFLUX_WRITER->value;

        $this->log->info('Publish', compact('exchange', 'routingKey'));

        return $channel->publish(serialize($dto), [], $exchange, $routingKey);
    }
}
