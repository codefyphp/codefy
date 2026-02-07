<?php

declare(strict_types=1);

namespace Codefy\Framework\Traits;

use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Factory\FileLoggerSmtpFactory;
use Psr\Log\LoggerInterface;

trait LoggerAware
{
    /**
     * FileLogger
     *
     * @throws \ReflectionException
     */
    public static function getLogger(): LoggerInterface
    {
        return FileLoggerFactory::getLogger();
    }

    /**
     * FileLogger with SMTP support.
     *
     * @throws \ReflectionException
     */
    public static function getSmtpLogger(): LoggerInterface
    {
        return FileLoggerSmtpFactory::getLogger();
    }
}
