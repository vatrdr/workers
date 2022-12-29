<?php declare(strict_types=1);

namespace Vatradar\Workers;

use Bunny\Async\Client;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;

class AmqpFactory
{
    private static PromiseInterface $instance;
    private static array $config;

    public static function new(): PromiseInterface
    {
        if(self::$instance instanceof PromiseInterface) {
            return self::$instance;
        }

        self::$config = [
            'host' => Env::get('RB_HOST'),
            'vhost' => Env::get('RB_VHOST'),
            'user' => Env::get('RB_USER'),
            'password' => Env::get('RB_PASS')
        ];

        return self::$instance = (new Client(Loop::get(), self::$config))->connect();
    }
}
