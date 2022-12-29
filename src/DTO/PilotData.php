<?php declare(strict_types=1);

namespace Workers\DTO;

use Vatradar\Dataobjects\Vatsim\General;
use Vatradar\Dataobjects\Vatsim\Pilot;

class PilotData implements VatsimDTOInterface
{
    private General $metadata;

    /** @var array<Pilot> $pilots */
    private array $pilots;

    private string $timestamp;

    public function __construct(General $metadata, array $pilots, string|int $timestamp) {
        $this->metadata  = $metadata;
        $this->pilots    = $pilots;
        $this->timestamp = (string) $timestamp;
    }

    public function getMetadata(): General {
        return $this->metadata;
    }

    public function getData(): array {
        return $this->pilots;
    }

    public function getTimestamp(): string {
        return $this->timestamp;
    }
}
