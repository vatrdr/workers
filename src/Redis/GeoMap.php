<?php declare(strict_types=1);

namespace Workers\Redis;

use React\Promise\PromiseInterface;
use Throwable;
use Vatradar\Dataobjects\Vatsim\Pilot;
use function React\Async\coroutine;
use function React\Promise\all;

final class GeoMap extends RedisBase
{
    public const TTL = 300;

    protected function process(array $data, string $ts): PromiseInterface {
        $this->log->debug('Process', ['vatsimTs' => $ts]);

        return coroutine(function() use ($data, $ts) {
            $geomapKey = sprintf('geomap:%s', $ts);
            $vectorKey = sprintf('vector:%s', $ts);

            $promises = [];

            /** @var Pilot $pilot */
            foreach($data as $pilot) {
                $promises[] = $this->geoAdd($geomapKey, $pilot);
                $promises[] = $this->vectorAdd($vectorKey, $pilot);
            }
            $promises[] = $this->expire($geomapKey, self::TTL);
            $promises[] = $this->expire($vectorKey, self::TTL);
            $promises[] = $this->set('geomap:currentkey', $geomapKey, self::TTL);
            $promises[] = $this->set('vector:currentkey', $vectorKey, self::TTL);
            $promises[] = $this->set('vatsimlatest', $ts, self::TTL);

            try {
                $results = yield all($promises);
            } catch(Throwable $e) {
                foreach($promises as $promise) {
                    $promise->cancel();
                }
                throw $e;
            }

            return $results;
        });
    }
}
