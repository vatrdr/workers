<?php declare(strict_types=1);

namespace Workers\Factory;

use Stash\Driver\Redis;
use Stash\Interfaces\PoolInterface;
use Stash\Pool;
use Workers\Env;

class StashFactory
{
    private static PoolInterface $instance;

    public static function get(): PoolInterface {
        if(isset(self::$instance)) {
            return self::$instance;
        }

        $driver = new Redis([
            'servers' => [
                [Env::get('REDIS_HOST'), Env::get('REDIS_PORT')],
            ],
        ]);

        self::$instance = new Pool($driver);

        return self::$instance;
    }
}
