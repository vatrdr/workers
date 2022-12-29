<?php declare(strict_types=1);

namespace Workers\DTO;

/**
 * Data Transfer Object for passing individual sections of the
 * VATSIM Data object through RabbitMQ.
 */
interface VatsimDTOInterface
{
    public function getTimestamp(): string;

    public function getMetadata(): array|object;

    public function getData(): mixed;
}
