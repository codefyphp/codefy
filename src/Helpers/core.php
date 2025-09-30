<?php

declare(strict_types=1);

namespace Codefy\Framework\Helpers;

use Codefy\CommandBus\Busses\SynchronousCommandBus;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\Containers\ContainerFactory;
use Codefy\CommandBus\Exceptions\CommandCouldNotBeHandledException;
use Codefy\CommandBus\Exceptions\UnresolvableCommandHandlerException;
use Codefy\CommandBus\Odin;
use Codefy\CommandBus\Resolvers\NativeCommandHandlerResolver;
use Codefy\Framework\Application;
use Codefy\Framework\Proxy\Codefy;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Support\CodefyMailer;
use Codefy\QueryBus\Busses\SynchronousQueryBus;
use Codefy\QueryBus\Enquire;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\Resolvers\NativeQueryHandlerResolver;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Opis\Database\Database;
use Qubus\Config\Collection;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\QueryBuilder;
use ReflectionException;

use function dirname;
use function getcwd;
use function is_int;
use function Qubus\Security\Helpers\__observer;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function file_exists;
use function in_array;
use function is_string;
use function realpath;
use function sprintf;
use function substr_count;
use function ucfirst;

/**
 * Get the available container instance.
 *
 * @param string|null $name
 * @param array $args
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
 * @throws TypeException
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
    if (file_exists(filename: $file = getcwd() . '/bootstrap/app.php')) {
        return require(realpath(path: $file));
    } elseif (file_exists(filename: $file = dirname(path: getcwd()) . '/bootstrap/app.php')) {
        return require(realpath(path: $file));
    } else {
        return require(realpath(path: dirname(path: getcwd()) . '/bootstrap/app.php'));
    }
}

/**
 * Gets the value of an environment variable.
 *
 * @param string $key
 * @param mixed|null $default
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    return \Qubus\Config\Helpers\env($key, $default);
}

/**
 * QueryBuilder database instance.
 *
 * @return QueryBuilder|null
 * @throws Exception
 */
function queryBuilder(): ?QueryBuilder
{
    return Codefy::$PHP->getDb();
}

/**
 * Database abstraction layer (dbal) instance.
 *
 * @return Database
 * @throws Exception
 */
function dbal(): Database
{
    return new Database(Codefy::$PHP->getDbConnection());
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

            if (
                    ! in_array(needle: $name, haystack: ['MIME-Version','to','cc','bcc','replyTo'], strict: true)
                    && !is_int($name)
            ) {
                $instance = $instance->withCustomHeader(name: (string) $name, value: $content);
            }
        }
    }

    // Set X-Mailer header
    $xMailer = __observer()->filter->applyFilter(
        'mail.xmailer',
        sprintf('CodefyPHP Framework %s', Codefy::$PHP::APP_VERSION)
    );
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

/**
 * Dispatches the given `$command` through
 * the CommandBus.
 *
 * @param Command $command
 * @throws ReflectionException
 * @throws TypeException
 * @throws CommandCouldNotBeHandledException
 * @throws UnresolvableCommandHandlerException
 */
function command(Command $command): void
{
    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    $odin->execute($command);
}

/**
 * Queries the given query and returns
 * a result if any.
 *
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function ask(Query $query): mixed
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    return $enquirer->execute($query);
}
