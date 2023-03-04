<?php

declare(strict_types=1);

namespace Workers\Redis;

use React\Promise\PromiseInterface;
use Throwable;
use Vatradar\Dataobjects\Cache\FiledPlan;
use Vatradar\Dataobjects\Vatsim\Pilot;
use function React\Async\coroutine;
use function React\Promise\all;

final class FlightPlan extends RedisBase
{
    public const TTL = 300;

    protected function process(array $data, string $ts): PromiseInterface
    {
        $this->log->debug('Process', ['vatsimTs' => $ts]);

        return coroutine(function () use ($data) {
            $promises = [];

            /** @var Pilot $pilot */
            foreach ($data as $pilot) {
                if ($pilot->flightPlan !== null) {
                    $cidKey = sprintf('vflightplan:%d', $pilot->cid);
                    $callsignKey = sprintf('vflightplan:%s', $pilot->callsign);

                    $plan = new FiledPlan();
                    $plan->callsign = $pilot->callsign;
                    $plan->cid = $pilot->cid;
                    $plan->flightplan = $pilot->flightPlan;

                    $promises[] = $this->flightplanAdd($cidKey, $plan, self::TTL);
                    $promises[] = $this->flightplanAdd($callsignKey, $plan, self::TTL);
                }
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
