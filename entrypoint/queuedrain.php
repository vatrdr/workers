<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use React\EventLoop\Loop;
use Workers\Env;
use Workers\Factory\AmqpFactory;
use Workers\Factory\LoggerFactory;
use Workers\QueueConsumer;
use Workers\Utils\BlockDetector;

require __DIR__ . '/../vendor/autoload.php';

$env = Dotenv::createImmutable(__DIR__ . '/../');
$env->safeLoad();
$loop = Loop::get();
$log = LoggerFactory::create('EntryPoint');
(new BlockDetector(Loop::get()))->start();
$amqp = AmqpFactory::create();

// Configure the names of the queues you wish to drain here.
$consumerDefs = [
    // QueueDrainWorker::class => Queue::QUEUE_ENUM->value,
];

$consumer = new QueueConsumer($amqp, $consumerDefs, (int) Env::get('RB_PREFETCH'));
$consumer->run();
$loop->run();
