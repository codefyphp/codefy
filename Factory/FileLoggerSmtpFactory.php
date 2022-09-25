<?php

declare(strict_types=1);

namespace Codefy\Foundation\Factory;

use Codefy\Foundation\Contracts\LoggerFactory;
use Codefy\Foundation\Factory\Traits\FileLoggerAware;
use Codefy\Foundation\Support\LocalStorage;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Qubus\Exception\Exception;
use Qubus\Log\Logger;
use Qubus\Log\Loggers\FileLogger;
use Qubus\Log\Loggers\SwiftMailerLogger;
use ReflectionException;
use SplObjectStorage;

use function Codefy\Foundation\Helpers\env;

class FileLoggerSmtpFactory implements LoggerFactory
{
    use FileLoggerAware;

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public static function getLogger(): LoggerInterface
    {
        $storage = new SplObjectStorage();

        $filesystem = LocalStorage::disk(name: 'logs');

        $storage->attach(
            object: new FileLogger(filesystem: $filesystem, threshold: LogLevel::INFO)
        );

        $mail = SwiftMailerSmtpFactory::create();

        if (env(key: 'LOGGER_FROM_EMAIL') !== null && env(key: 'LOGGER_TO_EMAIL') !== null) {
            $storage->attach(object: new SwiftMailerLogger(mailer: $mail, threshold: LogLevel::INFO, params: [
                'from' => env(key: 'LOGGER_FROM_EMAIL'),
                'to' => env(key: 'LOGGER_TO_EMAIL'),
                'subject' => env(key: 'LOGGER_EMAIL_SUBJECT'),
            ]));
        }

        return new Logger(loggers: $storage);
    }
}
