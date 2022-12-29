<?php declare(strict_types=1);

namespace Workers\Factory;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use React\EventLoop\Loop;
use Workers\Env;

class LoggerFactory
{
    public const CYCLE = 60;

    private static Logger $logger;

    public static function create(string $channel = 'worker'): Logger {
        if(!isset(static::$logger)) {
            static::$logger = static::new();
        }

        $new = static::$logger->withName($channel);

        Loop::addPeriodicTimer(static::CYCLE, static function() use ($new) {
            $new->reset();
        });

        return $new;
    }

    private static function new(): Logger {
        return (new Logger('workers'))
            ->pushHandler(new StreamHandler('php://stdout', Env::get('LOG_LEVEL')))
            ->pushProcessor(new MemoryUsageProcessor(true, true));
    }
}
