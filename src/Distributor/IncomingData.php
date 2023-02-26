<?php declare(strict_types=1);

namespace Workers\Distributor;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Vatradar\Dataobjects\Vatsim\VatsimData;
use Vatradar\Dataobjects\DTO\PilotData;
use Workers\Exchange;
use Workers\Factory\LoggerFactory;
use Workers\RoutingKey;
use Workers\WorkerInterface;
use function get_class;
use function React\Async\parallel;
use function Vatradar\basename;

class IncomingData implements WorkerInterface
{

    protected LoggerInterface $log;

    public function __construct() {
        $this->log = LoggerFactory::create(basename(get_class($this)));
    }

    /**
     * @param Channel $channel
     * @param Message $message
     * @param Client  $client
     *
     * @return PromiseInterface
     */
    public function work(Channel $channel, Message $message, Client $client): PromiseInterface {
        /** @var VatsimData $data */
        $data = unserialize($message->content, ['allowed_classes' => true]);

        return parallel([
            function() use ($channel, $data) {
                return $this->publish($channel, $data, Exchange::VATSIM_DISTRIBUTOR->value,
                    RoutingKey::LIVE_ALL->value
                );
            },
            function() use ($channel, $data) {
                return $this->publish($channel,
                    new PilotData($data->general, $data->pilots,
                        $data->general->updateTimestamp->format('U')
                    ),
                    Exchange::VATSIM_DISTRIBUTOR->value,
                    RoutingKey::LIVE_PILOT->value
                );
            },
        ]);
    }

    protected function publish(Channel $channel, mixed $message, string $exchange, string $routingKey): PromiseInterface {
        $this->log->debug('Publish', compact('exchange', 'routingKey'));
        return $channel->publish(serialize($message), [], $exchange, $routingKey);
    }
}
