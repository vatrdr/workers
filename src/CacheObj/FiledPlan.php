<?php declare(strict_types=1);

namespace Workers\CacheObj;

use Vatradar\Dataobjects\Vatsim\FlightPlan;

class FiledPlan
{
    public int         $cid;
    public string      $callsign;
    public ?FlightPlan $flightplan;
}
