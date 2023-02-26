<?php declare(strict_types=1);

namespace Vatradar\Dataobjects\Cache;

use Vatradar\Dataobjects\Vatsim\FlightPlan;

/**
 * A filed flightplan retrieved from the vatsim data.
 *
 * Effectively a dto/dso for a flightplan.
 */
class FiledPlan implements CacheInterface
{
    public int         $cid;
    public string      $callsign;
    public ?FlightPlan $flightplan; // vatsim can and does return null

    /**
     * @inheritDoc
     */
    public function array(): array {
        return [
            'cid'        => $this->cid,
            'callsign'   => $this->callsign,
            'flightplan' => $this->flightplan,
        ];
    }
}
