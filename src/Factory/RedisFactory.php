<?php declare(strict_types=1);

namespace Workers\Factory;

use Clue\React\Redis\RedisClient;
use React\EventLoop\Loop;
use Workers\Env;

class RedisFactory
{
    public static function create(): RedisClient {
        return new RedisClient(url: Env::get('REDIS_URL'), loop: Loop::get());
    }
}
