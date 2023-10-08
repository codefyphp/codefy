<?php

declare(strict_types=1);

namespace Codefy\Framework;

use Codefy\Framework\Factory\FileLoggerFactory;
use Psr\Log\LoggerInterface;
use ReflectionException;

final class Codefy
{
    /**
     * Application instance.
     *
     * @var Application $php
     */
    public static Application $PHP;

    /**
     * FileLogger
     *
     * @throws ReflectionException
     */
    public static function getLogger(): LoggerInterface
    {
        return FileLoggerFactory::getLogger();
    }
}
