<?php

declare(strict_types=1);

namespace Codefy\Framework\Factory;

use Codefy\Framework\Contracts\LoggerFactory;
use Codefy\Framework\Factory\Traits\FileLoggerAware;
use Codefy\Framework\Support\LocalStorage;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Qubus\Log\Logger;
use Qubus\Log\Loggers\FileLogger;
use ReflectionException;
use SplObjectStorage;

class FileLoggerFactory implements LoggerFactory
{
    use FileLoggerAware;

    /**
     * @throws ReflectionException
     */
    public static function getLogger(): LoggerInterface
    {
        $storage = new SplObjectStorage();

        $filesystem = LocalStorage::disk(name: 'logs');

        $storage->attach(
            object: new FileLogger(filesystem: $filesystem, threshold: LogLevel::INFO)
        );

        return new Logger(loggers: $storage);
    }
}
