<?php declare(strict_types=1);

namespace Workers\CacheObj;

class Vector
{
    public int $altitude;
    public int $heading;
    public int $groundspeed;

    public function __construct(int $altitude, int $heading, int $groundspeed) {
        $this->altitude    = $altitude;
        $this->heading     = $heading;
        $this->groundspeed = $groundspeed;
    }
}
