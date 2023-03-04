<?php

declare(strict_types=1);

namespace Workers;

use RuntimeException;
use function array_key_exists;

class Env
{
    public static function get(string $var): string
    {
        if (!array_key_exists($var, $_ENV)) {
            throw new RuntimeException('Environment variable not defined: ' . $var);
        }

        return $_ENV[$var];
    }
}
