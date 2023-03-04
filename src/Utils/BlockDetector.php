<?php

declare(strict_types=1);

namespace Workers\Utils;

use Closure;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use Workers\Factory\LoggerFactory;
use function get_class;
use function hrtime;
use function Vatradar\basename;

class BlockDetector
{
    protected LoopInterface   $loop;
    protected Closure         $tester;
    protected int             $triggerThreshold;
    protected int             $checkInterval;
    protected TimerInterface  $timer;
    protected int             $time;
    protected LoggerInterface $log;

    public function __construct(LoopInterface $loop, int $triggerThreshold = 1000, int $checkInterval = 10)
    {
        $this->loop = $loop;
        $this->triggerThreshold = $triggerThreshold;
        $this->checkInterval = $checkInterval;
        $this->log = LoggerFactory::create(basename(get_class($this)));

        $this->tester = function (int $time) {
            $timeDiff = (hrtime(true) - $time) / 1000000; //ns->ms

            if ($timeDiff > $this->triggerThreshold) {
                ($this->callback())($timeDiff);
            }
        };
    }

    private function callback(): callable
    {
        return function (float $delay) {
            $this->log->warning('[BLOCKING] LOOP BLOCKING DETECTED - Delay: ' . $delay . "ms\n");
        };
    }

    public function start(): void
    {
        if (isset($this->timer)) {
            return;
        }

        $this->timer = $this->loop->addPeriodicTimer($this->checkInterval, function () {
            $this->time = hrtime(true);

            $this->loop->futureTick(function () {
                ($this->tester)($this->time);
            });
        });
    }

    public function stop(): void
    {
        if (!isset($this->timer)) {
            return;
        }

        $this->loop->cancelTimer($this->timer);
        unset($this->timer);
    }
}
