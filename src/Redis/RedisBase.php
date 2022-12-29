<?php declare(strict_types=1);

namespace Workers\Redis;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Clue\React\Redis\RedisClient;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Vatradar\Dataobjects\Vatsim\Pilot;
use Workers\CacheObj\FiledPlan;
use Workers\CacheObj\TrackPoint;
use Workers\CacheObj\Vector;
use Workers\DTO\VatsimDTOInterface;
use Workers\Factory\LoggerFactory;
use Workers\Factory\RedisFactory;
use Workers\WorkerInterface;
use function get_class;
use function Vatradar\basename;

abstract class RedisBase implements WorkerInterface
{
    protected RedisClient     $redis;
    protected LoggerInterface $log;

    public function __construct() {
        $this->redis = RedisFactory::create();
        $this->log   = LoggerFactory::create(basename(get_class($this)));
    }

    public function __destruct() {
        $this->redis->end();
    }

    public function work(Channel $channel, Message $message, Client $client): PromiseInterface {
        /** @var VatsimDTOInterface $data */
        $data = unserialize($message->content, ['allowed_classes' => true]);

        return $this->process($data->getData(), $data->getTimestamp());
    }

    abstract protected function process(array $data, string $ts): PromiseInterface;

    protected function geoAdd(string $key, Pilot $pilot): PromiseInterface {
        // these values are a no-go in redis... it's close enough to the poles anyway
        $lat = ($pilot->latitude < 85.05112878) ? $pilot->latitude : 85.0511287;
        $lat = ($lat > -85.05112878) ? $lat : -85.0511287;

        return $this->redis->geoadd($key, $pilot->longitude, $lat, $pilot->cid);
    }

    protected function vectorAdd(string $key, Pilot $pilot): PromiseInterface {
        $vector = new Vector($pilot->altitude, $pilot->heading, $pilot->groundspeed);

        return $this->redis->hset($key, $pilot->cid, serialize($vector));
    }

    protected function trackAdd(string $key, TrackPoint $trackpoint, int $ttl): PromiseInterface {
        return $this->redis->rpush($key, serialize($trackpoint))
            ->then(function() use ($key, $ttl) { $this->expire($key, $ttl); });
    }

    protected function expire(string $key, int $ttl): PromiseInterface {
        return $this->redis->expire($key, $ttl);
    }

    protected function flightplanAdd(string $key, FiledPlan $plan, int $ttl): PromiseInterface {
        return $this->redis->set($key, serialize($plan), 'EX', $ttl);
    }

    protected function set(string $key, string $val, int $ttl = null): PromiseInterface {
        if($ttl) {
            return $this->redis->set($key, $val, 'EX', $ttl);
        }

        return $this->redis->set($key, $val);
    }

    protected function sortedSetAdd(string $key, string $val, int $expire): PromiseInterface {
        return $this->redis->zadd($key, $expire, $val);
    }
}
