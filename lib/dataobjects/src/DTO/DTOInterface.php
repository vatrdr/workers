<?php declare(strict_types=1);

namespace Vatradar\Dataobjects\DTO;

/**
 * Define the minimum required methods for a Data Transfer Object
 */
interface DTOInterface
{
    /**
     * Returns the timestamp in string form.
     *
     * Storage of timestamp left to the class; the only constraint is that
     * it must be returnable as a string, even if it's a simple integer timestamp.
     * This allows for ts flexibility and casting is still available downrange.
     *
     * @return string
     */
    public function getTimestamp(): string;

    /**
     * Whatever metadata you need for your DTO.
     *
     * @return array|object
     */
    public function getMetadata(): array|object;

    /**
     * Payload.
     *
     * @return mixed
     */
    public function getData(): mixed;
}
