<?php declare(strict_types=1);

namespace Vatradar\Dataobjects\Cache;

/**
 * Defines a Track Point of aeronautical data that includes location,
 * vector, and altitude information altogether.
 */
class TrackPoint implements CacheInterface
{
    public string $timestamp;
    public float  $latitude;
    public float  $longitude;
    public int    $altitude;
    public int    $heading;
    public int    $groundspeed;

    /**
     * @inheritDoc
     */
    public function array(): array {
        return [
            'timestamp'   => $this->timestamp,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'altitude'    => $this->altitude,
            'heading'     => $this->heading,
            'groundspeed' => $this->groundspeed,
        ];
    }
}
