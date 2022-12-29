<?php declare(strict_types=1);

namespace Workers\DTO;

class Generic implements VatsimDTOInterface
{

    private array $metaData;
    private array $data;
    private string $timestamp;

    public function __construct(array $metaData, array $data, string|int $timestamp) {
        $this->metaData = $metaData;
        $this->data = $data;
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
