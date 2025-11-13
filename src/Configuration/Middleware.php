<?php

declare(strict_types=1);

namespace Codefy\Framework\Configuration;

use Codefy\Framework\Support\DefaultMiddlewares;

class Middleware
{
    protected static array $customAliases = [];

    /**
     * Register additional middleware aliases.
     *
     * @param array $aliases
     * @return $this
     */
    public function alias(array $aliases): static
    {
        self::$customAliases = $aliases;

        return $this;
    }

    public static function defaultMiddlewares(): DefaultMiddlewares
    {
        return new DefaultMiddlewares()->merge(self::$customAliases);
    }
}
