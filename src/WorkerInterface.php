<?php declare(strict_types=1);

namespace Workers;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use React\Promise\PromiseInterface;

interface WorkerInterface
{
    public function work(Channel $channel, Message $message, Client $client): PromiseInterface;
}
