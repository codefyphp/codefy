<?php

declare(strict_types=1);

namespace Codefy\Framework\Helpers;

use Codefy\Framework\Application;
use Codefy\Framework\Codefy;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Support\CodefyMailer;
use Qubus\Config\Collection;
use Qubus\Dbal\Connection;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmBuilder;
use ReflectionException;

use function Qubus\Security\Helpers\__observer;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function file_exists;
use function in_array;
use function is_string;
use function rtrim;
use function sprintf;
use function substr_count;
use function ucfirst;

/**
 * Get the available container instance.
 *
 * @param  string|null  $name
 * @param  array  $args
 * @return mixed
 */
function app(?string $name = null, array $args = []): mixed
{
    /** @var Application $app */
    $app = get_fresh_bootstrap();

    if (is_null__(var: $name)) {
        return $app->getContainer();
    }
    return $app->getContainer()->make($name, $args);
}

/**
 * Get the available config instance.
 *
 * @param string $key
 * @param array|bool $set
 * @return mixed
 */
function config(string $key, array|bool $set = false): mixed
{
    if (!is_false__(var: $set)) {
        app(name: Collection::class)->setConfigKey($key, $set);
        return app(name: Collection::class)->getConfigKey($key);
    }

    return app(name: Collection::class)->getConfigKey($key);
}

/**
 * Retrieve a fresh instance of the bootstrap.
 *
 * @return mixed
 */
function get_fresh_bootstrap(): mixed
{
    if (file_exists(filename: $file = __DIR__ . '/../../../../../bootstrap/app.php')) {
        return require($file);
    } elseif (file_exists(filename: $file = __DIR__ . '/../../../../bootstrap/app.php')) {
        return require($file);
    } elseif (file_exists(filename: $file = __DIR__ . '/../../bootstrap/app.php')) {
        return require($file);
    } elseif (
        file_exists(
            filename: $file = rtrim(string: (string) env(key: 'APP_BASE_PATH'), characters: '/') . '/bootstrap/app.php'
        )
    ) {
        return require($file);
    } else {
        return require(__DIR__ . '/../bootstrap/app.php');
    }
}

/**
 * Gets the value of an environment variable.
 *
 * @param string $key
 * @param mixed|null $default
 * @return mixed|null
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}

/**
 * OrmBuilder database instance.
 *
 * @return OrmBuilder|null
 * @throws Exception
 */
function orm(): ?OrmBuilder
{
    return Codefy::$PHP->getDB();
}

/**
 * Dbal database instance.
 *
 * @return Connection
 * @throws Exception
 */
function dbal(): Connection
{
    return Codefy::$PHP->getDbConnection();
}

/**
 * Alternative to PHP's native mail function with SMTP support.
 *
 * This is a simple mail function to see for testing or for
 * sending simple email messages.
 *
 * @param string|array $to Recipient(s)
 * @param string $subject Subject of the email.
 * @param string $message The email body.
 * @param array $headers An array of headers.
 * @param array $attachments An array of attachments.
 * @return bool
 * @throws Exception|ReflectionException|\PHPMailer\PHPMailer\Exception
 */
function mail(string|array $to, string $subject, string $message, array $headers = [], array $attachments = []): bool
{
    // Instantiate CodefyMailer.
    $instance = new CodefyMailer(config: app(name: 'codefy.config'));

    // Set the mailer transport.
    $func = sprintf('with%s', ucfirst(config(key: 'mailer.mail_transport')));
    $instance = $instance->{$func}();

    // Detect HTML markdown.
    if (substr_count(haystack: $message, needle: '</') >= 1) {
        $instance = $instance->withHtml(isHtml: true);
    }

    // Build recipient(s).
    $instance = $instance->withTo(address: $to);

    // Set from name and from email from environment variables.
    $fromName = __observer()->filter->applyFilter('mail.from.name', env(key: 'MAILER_FROM_NAME'));
    $fromEmail = __observer()->filter->applyFilter('mail.from.email', env(key: 'MAILER_FROM_EMAIL'));
    // Set charset
    $charset = __observer()->filter->applyFilter('mail.charset', 'utf-8');

    // Set email subject and body.
    $instance = $instance->withSubject(subject: $subject)->withBody(data: $message);

    // Check for other headers and loop through them.
    if (!empty($headers)) {
        foreach ($headers as $name => $content) {
            if ($name === 'cc') {
                $instance = $instance->withCc(address: $content);
            }

            if ($name === 'bcc') {
                $instance = $instance->withBcc(address: $content);
            }

            if ($name === 'replyTo') {
                $instance = $instance->withReplyTo(address: $content);
            }

            if (! in_array(needle: $name, haystack: ['MIME-Version','to','cc','bcc','replyTo'], strict: true)) {
                $instance = $instance->withCustomHeader(name: $name, value: $content);
            }
        }
    }

    // Set X-Mailer header
    $xMailer = __observer()->filter->applyFilter('mail.xmailer', sprintf('CodefyPHP Framework %s', Application::APP_VERSION));
    $instance = $instance->withXMailer(xmailer: $xMailer);

    // Set email charset
    $instance = $instance->withCharset(charset: $charset ?: 'utf-8');

    // Check if there are attachments and loop through them.
    if (!empty($attachments)) {
        foreach ($attachments as $filename => $filepath) {
            $filename = is_string(value: $filename) ? $filename : '';
            $instance = $instance->withAttachment(path: $filepath, name: $filename);
        }
    }

    // Set sender.
    $instance = $instance->withFrom(address: $fromEmail, name: $fromName ?: '');

    try {
        return $instance->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        FileLoggerFactory::getLogger()->error($e->getMessage(), ['function' => '\Codefy\Framework\Helpers\mail']);
        return false;
    }
}
