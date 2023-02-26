<?php declare(strict_types=1);

namespace Vatradar\Dataobjects\DTO;

use Vatradar\Dataobjects\Vatsim\General;
use Vatradar\Dataobjects\Vatsim\Pilot;

/**
 * DTO subcontainer for splitting out the Vatsim pilot data
 * and transferring that as its own entity.
 *
 * Metadata is the general data from the vatsim json
 * Data is an array of Pilot objects from vatsim json
 */
class PilotData implements DTOInterface
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
