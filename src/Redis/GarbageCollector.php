<?php declare(strict_types=1);

namespace Workers\Redis;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Throwable;
use function React\Async\coroutine;
use function React\Promise\all;

final class GarbageCollector extends RedisBase
{
    public const BACKOFF = 15;

    public function sweep(): PromiseInterface {
        $this->log->debug('Initiate Sweep');

        $promises   = [];
        $promises[] = $this->sweepSortedSet('vdepartures:*')->then(function(int $recs) {
            $this->log->info('Departures', ['removed' => $recs]);
        });

        $promises[] = $this->sweepSortedSet('varrivals:*')->then(function(int $recs) {
            $this->log->info('Arrivals', ['removed' => $recs]);
        });

        return all($promises);
    }

    protected function sweepSortedSet(string $search): PromiseInterface {
        return coroutine(function() use ($search) {
            $airports = yield $this->redis->keys($search);

            $score    = time() - self::BACKOFF;
            $promises = [];
            foreach($airports as $dep) {
                $promises[] = $this->redis->zremrangebyscore($dep, '-inf', $score);
            }

            try {
                $numRecords = yield all($promises);
            } catch(Throwable $e) {
                foreach($promises as $promise) {
                    $promise->cancel();
                }
                throw $e;
            }

            $num = 0;
            foreach($numRecords as $r) {
                $num += $r;
            }

            return $num;
        });
    }

    protected function process(array $data, string $ts): PromiseInterface {
        // don't know how you got here, but this doesn't do what you think it does.
        $def = new Deferred();
        $def->reject('Method Not Implemented');
        return $def->promise();
    }
}
