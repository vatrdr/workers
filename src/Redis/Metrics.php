<?php declare(strict_types=1);

namespace Workers\Redis;

use React\Promise\PromiseInterface;
use Throwable;
use function array_key_exists;
use function React\Async\coroutine;
use function React\Promise\all;
use function serialize;

class Metrics extends RedisBase
{

    protected function process(array $data, string $ts): PromiseInterface {
        $this->log->debug('Process', ['vatsimTs' => $ts]);

        return coroutine(function() use ($data, $ts) {
            $promises = [];

            foreach(['pilot', 'airport'] as $metric) {
                if(array_key_exists($metric, $data)) {
                   $promises[] = $this->set('metrics:'.$metric, serialize($data[$metric]));
                }
            }

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
