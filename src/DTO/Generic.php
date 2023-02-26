<?php declare(strict_types=1);

namespace Vatradar\Dataobjects\DTO;

/**
 * Generic data transfer object that can be used to store/transfer
 * data in message queue or cache by wrapping data in the DTO.
 */
class Generic implements DTOInterface
{
    private array  $metaData;
    private array  $data;
    private string $timestamp;

    public function __construct(array $metaData, array $data, string|int $timestamp) {
        $this->metaData  = $metaData;
        $this->data      = $data;
        $this->timestamp = (string) $timestamp;

    }

    public function getTimestamp(): string {
        return $this->timestamp;
    }

    public function getMetadata(): array {
        return $this->metaData;
    }

    public function getData(): array {
        return $this->data;
    }
}
