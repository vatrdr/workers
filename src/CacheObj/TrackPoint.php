<?php declare(strict_types=1);

namespace Workers\CacheObj;

class TrackPoint
{
    public string $timestamp;
    public float  $latitude;
    public float  $longitude;
    public int    $altitude;
    public int    $heading;
    public int    $groundspeed;

}
