<?php

declare(strict_types=1);

namespace Workers;

enum Exchange: string
{
    case VATSIM_DISTRIBUTOR = 'vatsim.distributor';
    case VATSIM_INPUT = 'vatsim.input';
}
