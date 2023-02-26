<?php declare(strict_types=1);

namespace Workers\Influx;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use InfluxDB2\WriteApi;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Throwable;
use Vatradar\Dataobjects\DTO\Influx;
use Workers\Factory\InfluxFactory;
use Workers\Factory\LoggerFactory;
use Workers\WorkerInterface;
use function count;
use function get_class;
use function React\Async\coroutine;

class Writer implements WorkerInterface
{
    protected int $batchSize = 500;
    protected bool $batch = true;
    protected string $bucket;
    protected WriteApi $writer;
    protected LoggerInterface $log;

    public function __construct() {
        $this->log = LoggerFactory::create(\Vatradar\basename(get_class($this)));
    }

    public function __destruct() {
        $this->writer->close();
    }

    public function work(Channel $channel, Message $message, Client $client): PromiseInterface {

        /** @var Influx $data */
        $data = unserialize($message->content, ['allowed_classes' => true]);

        $this->batch = $data->getMetadata()['batch'];
        $this->bucket = $data->getMetadata()['bucket'];
        $this->writer = InfluxFactory::getWriter($this->batch, $this->batchSize);

        return $this->process($data->getData(), $data->getTimestamp());
    }

    protected function process(array $data, string $ts): PromiseInterface {
        $this->log->info('Process', ['vatsimTs' => $ts, 'points' => count($data)]);

        return coroutine(function() use ($data) {
            try {
                foreach($data as $point) {
                    $this->writer->write(data: $point, bucket: $this->bucket);
                }

                $this->writer->close();
            } catch (Throwable $e) {
                // TODO: handle influx getWriter failures
                $this->log->error('Influx Writer Failure', ['code' => $e->getCode(), 'error' => $e->getMessage()]);
            }
        });
    }
}
