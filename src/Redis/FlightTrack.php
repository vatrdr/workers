<?php

declare(strict_types=1);

namespace Workers\Redis;

use React\Promise\PromiseInterface;
use Throwable;
use Vatradar\Dataobjects\Cache\TrackPoint;
use Vatradar\Dataobjects\Vatsim\Pilot;
use function React\Async\coroutine;
use function React\Promise\all;

final class FlightTrack extends RedisBase
{
    public const TTL_TRACK = 21600;
    public const TTL_AIRPORT = 300;

    protected function process(array $data, string $ts): PromiseInterface
    {
        $this->log->debug('Process', ['vatsimTs' => $ts]);

        return coroutine(function () use ($data) {
            $promises = [];

            /** @var Pilot $pilot */
            foreach ($data as $pilot) {

                if ($pilot->flightPlan !== null) {
                    $departure = preg_replace('/[^[:alnum:]]/', '', $pilot->flightPlan->departure);
                    $arrival = preg_replace('/[^[:alnum:]]/', '', $pilot->flightPlan->arrival);
                } else {
                    $departure = 'ZZZZ';
                    $arrival = 'ZZZZ';
                }
                if (empty($departure)) {
                    $departure = 'ZZZZ';
                }
                if (empty($arrival)) {
                    $arrival = 'ZZZZ';
                }

                $trackKey = sprintf('vtrack:%d:%s:%s:%s', $pilot->cid, $pilot->callsign, $departure, $arrival);
                $depKey = sprintf('vdepartures:%s', $departure);
                $arrKey = sprintf('varrivals:%s', $arrival);

                $point = new TrackPoint();
                $point->timestamp = $pilot->lastUpdated->format('U');
                $point->latitude = $pilot->latitude;
                $point->longitude = $pilot->longitude;
                $point->altitude = $pilot->altitude;
                $point->heading = $pilot->heading;
                $point->groundspeed = $pilot->groundspeed;

                $promises[] = $this->trackAdd($trackKey, $point, self::TTL_TRACK);
                $promises[] = $this->sortedSetAdd($depKey, (string) $pilot->cid, (time() + self::TTL_AIRPORT));
                $promises[] = $this->sortedSetAdd($arrKey, (string) $pilot->cid, (time() + self::TTL_AIRPORT));
            }

            try {
                $results = yield all($promises);
            } catch (Throwable $e) {
                foreach ($promises as $promise) {
                    $promise->cancel();
                }
                throw $e;
            }

            return $results;
        });
    }
}
