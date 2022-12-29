<?php declare(strict_types=1);

namespace Workers;

enum Queue: string
{
    case REDIS_FLIGHTPLANDATA = 'redis.flightplan';
    case REDIS_GEOMAP         = 'redis.geo';
    case REDIS_METRICS        = 'redis.metrics';
    case VATSIM_INPUT         = 'vatsim.input';
    case VATSIM_METRICS       = 'vatsim.metrics';
    case REDIS_TRACKDATA      = 'redis.track';
    case INFLUX_METRICS       = 'influx.metrics';
    case INFLUX_WRITER        = 'influx.writer';
    case INFLUX_POSITION      = 'influx.position';

}
