<?php

declare(strict_types=1);

namespace Codefy\Framework\Support\Traits;

use Codefy\Framework\Proxy\Codefy;

// @phpstan-ignore trait.unused
trait ContainerAware
{
    public static function make(): static
    {
        return Codefy::$PHP->make(name: static::class);
    }
}
