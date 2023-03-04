<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use React\EventLoop\Loop;
use Workers\Distributor\IncomingData;
use Workers\Distributor\MetricsDistributor;
use Workers\Env;
use Workers\Factory\AmqpFactory;
use Workers\Factory\LoggerFactory;
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
    IncomingData::class => Queue::VATSIM_INPUT->value,
    MetricsDistributor::class => Queue::VATSIM_METRICS->value,
];

$consumer = new QueueConsumer($amqp, $consumerDefs, (int) Env::get('RB_PREFETCH'));
$consumer->registerGc($loop, Env::get('GC_INTERVAL'));

$consumer->run();
$loop->run();
