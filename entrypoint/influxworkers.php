<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use React\EventLoop\Loop;
use Workers\Env;
use Workers\Factory\AmqpFactory;
use Workers\Factory\LoggerFactory;
use Workers\Influx\Metrics;
use Workers\Influx\PositionData;
use Workers\Queue;
use Workers\QueueConsumer;
use Workers\Utils\BlockDetector;

require __DIR__ . '/../vendor/autoload.php';

$env = Dotenv::createImmutable(__DIR__ . '/../');
$env->safeLoad();
$loop = Loop::get();
$log = LoggerFactory::create('EntryPoint');
(new BlockDetector(Loop::get()))->start();
$amqp = AmqpFactory::create();

$consumerDefs = [
    Metrics::class => Queue::INFLUX_METRICS->value,
    PositionData::class => Queue::INFLUX_POSITION->value,
];

$queueConsumer = new QueueConsumer($amqp, $consumerDefs, (int) Env::get('RB_PREFETCH'));
$queueConsumer->registerGc($loop, Env::get('GC_INTERVAL'));

$queueConsumer->run();
$loop->run();
