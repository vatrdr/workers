<?php

declare(strict_types=1);

namespace Workers;

enum RoutingKey: string
{
    case LIVE_ALL = 'live.all';
    case LIVE_PILOT = 'live.pilot';
    case REDIS_METRICS = 'redis.metrics';
    case INFLUX_METRICS = 'influx.metrics';
    case INFLUX_WRITER = 'influx.writer';
}
