<?php

declare(strict_types=1);

// TODO: Handle "Bunny\Exception\ClientException: Broken pipe or closed connection"

namespace Workers\Factory;

use Bunny\Async\Client;
use React\EventLoop\Loop;
use Workers\Env;

class AmqpFactory
{
    public static function create(): Client
    {
        $config = [
            'host' => Env::get('RB_HOST'),
            'vhost' => Env::get('RB_VHOST'),
            'user' => Env::get('RB_USER'),
            'password' => Env::get('RB_PASS'),
        ];

        return new Client(Loop::get(), $config);
    }
}
