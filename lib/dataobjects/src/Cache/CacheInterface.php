<?php declare(strict_types=1);

namespace Vatradar\Dataobjects\Cache;

/**
 * Required methods for a cache object.
 *
 * These objects are not meant to be super-complicated, they're merely
 * a means to standardize how data is stored. Most will likely have
 * publicly-scoped variables that serve as definitions and can be
 * manipulated directly.
 *
 * Yes, there's probably a better way to do this. Such is life.
 */
interface CacheInterface
{

    /**
     * Return a cache object as an associative array.
     *
     * @return array
     */
    public function array(): array;
}
