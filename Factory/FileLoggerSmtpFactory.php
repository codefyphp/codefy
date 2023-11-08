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
use Qubus\Log\Loggers\PHPMailerLogger;
use ReflectionException;
use SplObjectStorage;

use function Codefy\Framework\Helpers\env;

class FileLoggerSmtpFactory implements LoggerFactory
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

        $mail = PHPMailerSmtpFactory::create();

        if (env(key: 'LOGGER_FROM_EMAIL') !== null && env(key: 'LOGGER_TO_EMAIL') !== null) {
            $storage->attach(object: new PHPMailerLogger(mailer: $mail, threshold: LogLevel::INFO, params: [
                'from' => env(key: 'LOGGER_FROM_EMAIL'),
                'to' => env(key: 'LOGGER_TO_EMAIL'),
                'subject' => env(key: 'LOGGER_EMAIL_SUBJECT'),
            ]));
        }

        return new Logger(loggers: $storage);
    }
}
