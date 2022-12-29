<?php declare(strict_types=1);

namespace Workers\CacheObj;

class Point
{
    public float $latitude;
    public float $longitude;

    public function __construct(float $latitude, float $longitude) {
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
    }
}
