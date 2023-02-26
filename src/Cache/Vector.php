<?php declare(strict_types=1);

namespace Vatradar\Dataobjects\Cache;

/**
 * Vector data.
 *
 * Bit of a misnomer, since it contains altitude as well, but vector
 * seemed like the best name for it.
 */
class Vector implements CacheInterface
{
    public int $altitude;
    public int $heading;
    public int $groundspeed;

    public function __construct(int $altitude, int $heading, int $groundspeed) {
        $this->altitude    = $altitude;
        $this->heading     = $heading;
        $this->groundspeed = $groundspeed;
    }

    /**
     * @inheritDoc
     */
    public function array(): array {
        return [
            'altitude'    => $this->altitude,
            'heading'     => $this->heading,
            'groundspeed' => $this->groundspeed,
        ];
    }
}
