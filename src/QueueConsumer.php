<?php declare(strict_types=1);

namespace Workers;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;
use Throwable;
use Workers\Factory\LoggerFactory;
use function gc_collect_cycles;
use function substr;
use function Vatradar\basename;

/**
 * Basic AMQP Queue consumer class used to instantiate an async
 * consumer and run a worker to consume that queue.
 */
class QueueConsumer
{
    protected int             $prefetch;
    protected Client          $amqp;
    protected LoggerInterface $log;
    protected array           $consumerDefs;
    protected array           $consumers;

    /**
     * @param Client $amqp
     * @param array  $consumerDefs
     * @param int    $prefetch
     */
    public function __construct(Client $amqp, array $consumerDefs, int $prefetch = 1) {
        $this->prefetch     = $prefetch;
        $this->amqp         = $amqp;
        $this->log          = LoggerFactory::create(basename(__CLASS__));
        $this->consumerDefs = $consumerDefs;
        $this->consumers    = [];
    }

    public function __destruct() {
        $this->log->debug('AMQP Disconnect');
        $this->amqp->disconnect();
    }

    public function run(): void {
        $this->amqp->connect()->then(function() {
            $this->start();
        });
    }

    private function start(): void {
        $this->log->info('Consumer Startup Initiated');

        foreach($this->consumerDefs as $class => $queue) {
            $this->log->info('Start', ['worker' => basename($class)]);

            $this->consumers[$class] = $this->amqp->channel()->then(function(Channel $chan): PromiseInterface {
                $this->log->info('Channel Open ', ['channelId' => $chan->getChannelId()]);
                return $chan->qos(0, $this->prefetch)->then(function() use ($chan) {
                    return $chan;
                });
            })->then(function(Channel $chan) use ($class, $queue): Channel {
                $worker = new $class();

                $this->log->info('Waiting for work ',
                    ['worker' => basename($class), 'channelId' => $chan->getChannelId()]
                );

                $chan->consume(
                    function(Message $message, Channel $channel, Client $client) use ($worker, $class): void {
                        try {
                            $worker->work($channel, $message, $client)->then(function() use ($channel, $message) {
                                $channel->ack($message);
                            }, function() use ($class, $channel, $message) {
                                $this->log->warning('NACK',
                                    ['worker' => $class, 'truncatedMessage' => substr($message->content, 0, 200)]
                                );
                                $channel->nack($message);
                            });
                        } catch(Throwable $e) {
                            $this->log->critical('EXCEPTION NACK', ['worker' => $class, 'reason' => $e->getMessage(
                            ), 'truncatedMessage'                            => substr($message->content, 0, 200)]);
                            $channel->nack($message);
                            /**
                             * INITIAL / PRIMARY ERROR HANDLING:
                             *
                             * If we getClient back a complete failure that necessitates a NACK message,
                             * we go ahead and just kill the worker and let it be restarted.  Once
                             * the root cause is determined, we add logic to address the issue.
                             */
                            exit(1);
                        }
                    },
                    $queue
                );

                return $chan;
            });
        }
    }

    public function registerGc(LoopInterface $loop, $interval): TimerInterface {
        return $loop->addPeriodicTimer($interval, function() use ($loop) {
            $this->cycle();

            $loop->addTimer(2.0, function() {
                gc_collect_cycles();
            });
        });
    }

    public function cycle(): void {
        $this->log->info('Recycling Consumers');
        $this->stop();
        $this->start();
    }

    private function stop(): void {
        $this->log->info('Consumer Stop Initiated');

        foreach($this->consumers as $name => $consumer) {
            $this->log->info('Stop', ['worker' => basename($name)]);

            $consumer->then(function(Channel $chan) {
                return $chan->close();
            });

            $this->consumers[$name] = null;
        }
    }
}
