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
use VatRadar\DataObjects\DTO\Influx;
use VatRadar\DataObjects\DTO\PilotData;
use VatRadar\DataObjects\Vatsim\Pilot;
use Workers\Exchange;
use Workers\Factory\LoggerFactory;
use Workers\RoutingKey;
use Workers\WorkerInterface;
use function compact;
use function get_class;
use function serialize;
use function unserialize;
use function Vatradar\basename;

class PositionData implements WorkerInterface
{
    protected LoggerInterface $log;

    public function __construct()
    {
        $this->log = LoggerFactory::create(basename(get_class($this)));
    }

    public function work(Channel $channel, Message $message, Client $client): PromiseInterface
    {
        /** @var PilotData $data */
        $data = unserialize($message->content, ['allowed_classes' => true]);

        $dto = $this->process($data->getData(), $data->getTimestamp());

        $exchange = Exchange::VATSIM_DISTRIBUTOR->value;
        $routingKey = RoutingKey::INFLUX_WRITER->value;

        $this->log->info('Publish', compact('exchange', 'routingKey'));

        return $channel->publish(serialize($dto), [], $exchange, $routingKey);
    }

    protected function process(array $pilots, string $ts): Influx
    {
        $points = [];

        /** @var Pilot $pilot */
        foreach ($pilots as $pilot) {
            $coords = $this->newPoint('coordinates', $pilot);
            $coords->addField('latitude', $pilot->latitude)
                ->addField('longitude', $pilot->longitude)
                ->time($ts, WritePrecision::S);

            $points[] = $coords;

            $vector = $this->newPoint('vector', $pilot);
            $vector->addField('heading', $pilot->heading)
                ->addField('altitude', $pilot->altitude)
                ->addField('groundspeed', $pilot->groundspeed)
                ->time($ts, WritePrecision::S);

            $points[] = $vector;
        }

        return new Influx($points, $ts, 'position_data');
    }

    protected function newPoint(string $measurement, Pilot $pilot): Point
    {
        $point = Point::measurement($measurement);

        $point->addTag('cid', (string) $pilot->cid)
            ->addTag('callsign', $pilot->callsign);

        if ($pilot->flightPlan) {
            $point->addTag('departure', $pilot->flightPlan->departure)
                ->addTag('arrival', $pilot->flightPlan->arrival);
        } else {
            $point->addTag('departure', 'ZZZZ')
                ->addTag('arrival', 'ZZZZ');
        }

        return $point;
    }
}
