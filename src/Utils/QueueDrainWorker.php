<?php declare(strict_types=1);

namespace Workers\Utils;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Workers\WorkerInterface;

class QueueDrainWorker implements WorkerInterface
{

    public function work(Channel $channel, Message $message, Client $client): PromiseInterface {
        $deferred = new Deferred();
        $deferred->resolve(true);
        return $deferred->promise();
    }
}
