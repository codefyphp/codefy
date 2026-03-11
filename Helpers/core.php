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
use Codefy\Framework\Codefy;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Factory\FileLoggerSmtpFactory;
use Codefy\Framework\Support\CodefyMailer;
use Codefy\QueryBus\Busses\SynchronousQueryBus;
use Codefy\QueryBus\Enquire;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\Resolvers\NativeQueryHandlerResolver;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Psr\Http\Message\ResponseInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Dbal\Connection;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmBuilder;
use Qubus\Http\Factories\HtmlResponseFactory;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Exceptions\RouteParamFailedConstraintException;
use Qubus\Routing\Router;
use Qubus\View\Renderer;
use ReflectionException;

use function dirname;
use function getcwd;
use function is_array;
use function is_int;
use function Qubus\Security\Helpers\__observer;
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
 * @param class-string|string|null $name
 * @param array<string, class-string> $args
 * @return ($name is null ? Application : mixed)
 */
function app(?string $name = null, array $args = []): mixed
{
    static $app;

    if (is_null__($app)) {
        /** @var Application $app */
        $app = get_fresh_bootstrap();
    }

    if (is_null__(var: $name)) {
        return $app->getContainer();
    }
    return $app->getContainer()->make($name, $args);
}

/**
 * Get the available config instance.
 *
 * @param string|array|null $key
 * @param array|bool $set
 * @return ($key is null ? ConfigContainer : mixed)
 */
function config(string|array|null $key = null, mixed $set = ''): mixed
{
    if (is_null__($key)) {
        return app(name: 'codefy.config');
    }

    if (is_array($key)) {
        app(name: 'codefy.config')->setConfigKey($key[0], $key[1]);
    } elseif (is_array($set)) {
        app(name: 'codefy.config')->setConfigKey($key, $set);
    }

    return app(name: 'codefy.config')->getConfigKey($key, $set);
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
 * @return mixed|null
 */
function env(string $key, mixed $default = null): mixed
{
    return \Qubus\Config\Helpers\env($key, $default);
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
    $func = sprintf('with%s', ucfirst(config()->string(key: 'mailer.mail_transport')));
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
        sprintf('CodefyPHP Framework %s', Application::APP_VERSION)
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
 * @throws \ReflectionException
 * @throws UnresolvableCommandHandlerException
 * @throws CommandCouldNotBeHandledException
 * @throws TypeException
 */
function command(Command $command): void
{
    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config()->array(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    $odin->execute($command);
}

/**
 * Queries the given query and returns
 * a result if any.
 *
 * @throws \ReflectionException
 * @throws UnresolvableQueryHandlerException
 * @throws TypeException
 */
function ask(Query $query): mixed
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config()->array(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    return $enquirer->execute($query);
}

/**
 * @param array<mixed>|string $template
 * @param array<array-key, mixed> $data
 * @return ResponseInterface
 * @throws \Exception
 */
function view(array|string $template, array $data = []): ResponseInterface
{
    /** @var Renderer $view */
    $view = Codefy::$PHP->make(name: Renderer::class);

    // @phpstan-ignore method.notFound
    return HtmlResponseFactory::create($view->render($template, $data));
}

/**
 * Generate url's from named routes.
 *
 * @param string $name Name of the route.
 * @param array $params Data parameters.
 * @return string The url.
 * @throws NamedRouteNotFoundException
 * @throws RouteParamFailedConstraintException
 */
function route(string $name, array $params = []): string
{
    /** @var Router $route */
    $route = app('router');
    return $route->url($name, $params);
}

/**
 * Return an array of system user roles.
 *
 * @return array<array-key, mixed>
 * @throws Exception
 */
function get_system_roles(): array
{
    $userRoles = [];
    $roles = Codefy::$PHP->configContainer->array(key: 'rbac.roles');
    foreach ($roles as $role => $description) {
        $userRoles[] = $role;
    }

    return $userRoles;
}

/**
 * @param string|\Stringable $level
 * @param string $message
 * @param array<mixed> $context
 * @return void
 */
function logger(string|\Stringable $level, string $message, array $context = []): void
{
    try {
        FileLoggerFactory::getLogger()->{$level}($message, $context);
    } catch (\ReflectionException $e) {
        error_log($e->getMessage());
    }
}

/**
 * @param string|\Stringable $level
 * @param string $message
 * @param array<mixed> $context
 * @return void
 * @throws TypeException
 */
function smtp_logger(string|\Stringable $level, string $message, array $context = []): void
{
    try {
        FileLoggerSmtpFactory::getLogger()->{$level}($message, $context);
    } catch (\ReflectionException $e) {
        error_log($e->getMessage());
    }
}
