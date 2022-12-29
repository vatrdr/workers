<?php declare(strict_types=1);

namespace Vatradar;

use function basename as bn;

function basename(string $class): string
{
    return bn(str_replace('\\', DIRECTORY_SEPARATOR, $class));
}
