<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use React\EventLoop\Loop;
use Workers\Env;
use Workers\Factory\AmqpFactory;
use Workers\Factory\LoggerFactory;
use Workers\Queue;
use Workers\QueueConsumer;
use Workers\Redis\FlightPlan;
use Workers\Redis\FlightTrack;
use Workers\Redis\GarbageCollector;
use Workers\Redis\GeoMap;
use Workers\Redis\Metrics;
use Workers\Utils\BlockDetector;

require __DIR__ . '/../vendor/autoload.php';

$env = Dotenv::createImmutable(__DIR__ . '/../');
$env->safeLoad();
$loop = Loop::get();
$log = LoggerFactory::create('EntryPoint');
(new BlockDetector(Loop::get()))->start();
$amqp = AmqpFactory::create();

$consumerDefs = [
    GeoMap::class => Queue::REDIS_GEOMAP->value,
    FlightPlan::class => Queue::REDIS_FLIGHTPLANDATA->value,
    FlightTrack::class => Queue::REDIS_TRACKDATA->value,
    Metrics::class => Queue::REDIS_METRICS->value,
];

$queueConsumer = new QueueConsumer($amqp, $consumerDefs, (int) Env::get('RB_PREFETCH'));
$queueConsumer->registerGc($loop, Env::get('GC_INTERVAL'));


$loop->addPeriodicTimer(Env::get('REDIS_GC_INTERVAL'), static function () use ($log) {
    $log->info('Start', ['worker' => 'GarbageCollector']);
    $gc = new GarbageCollector();

    ($gc->sweep())->then(function () use ($log) {
        $log->info('GC Run Complete');
        $log->reset();
    });

    $gc = null;
});

$queueConsumer->run();
$loop->run();
