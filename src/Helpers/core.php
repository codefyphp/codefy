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
use Codefy\Framework\Auth\Gate;
use Codefy\Framework\Http\RequestContext;
use Codefy\Framework\Proxy\Codefy;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Queue\NodeQueue;
use Codefy\Framework\Queue\ShouldQueue;
use Codefy\Framework\Support\CodefyMailer;
use Codefy\Framework\Support\Server;
use Codefy\QueryBus\Busses\SynchronousQueryBus;
use Codefy\QueryBus\Enquire;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\Resolvers\NativeQueryHandlerResolver;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Gravatar\Image;
use Gravatar\Profile;
use Psr\Http\Message\ResponseInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Exception\Http\HttpExceptionFactory;
use Qubus\Expressive\Connection;
use Qubus\Expressive\QueryBuilder;
use Qubus\Http\Factories\HtmlResponseFactory;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Exceptions\RouteParamFailedConstraintException;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Route\RouteAttributes;
use Qubus\View\Renderer;
use ReflectionException;
use RuntimeException;
use Throwable;

use function dirname;
use function error_log;
use function filter_var;
use function getcwd;
use function is_int;
use function parse_url;
use function preg_match;
use function preg_replace;
use function Qubus\Security\Helpers\__observer;
use function Qubus\Security\Helpers\esc_attr__;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_null__;
use function file_exists;
use function in_array;
use function is_string;
use function realpath;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;
use function substr_count;
use function ucfirst;

use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

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
 * @param array<string, mixed>|string|null  $key
 * @param mixed $default
 * @return mixed
 */
function config(string|array|null $key, mixed $default = ''): mixed
{
    if (is_null__($key)) {
        return app(name: 'codefy.config');
    }

    if (is_array($key)) {
        app(name: 'codefy.config')->setConfigKey($key[0], $key[1]);
    }

    return app(name: 'codefy.config')->getConfigKey($key, $default);
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
 * Database abstraction layer global function.
 *
 * @throws Exception
 */
function dbal(): Connection
{
    return Codefy::$PHP->getDbConnection();
}

/**
 * QueryBuilder global function.
 *
 * @return QueryBuilder|null
 * @throws Exception
 */
function queryBuilder(): ?QueryBuilder
{
    return dbal()->queryBuilder();
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

/**
 * Normalize a URL by collapsing multiple consecutive slashes into one,
 * but preserve the scheme's "://" (for http/https) and do not touch query or fragment.
 *
 * Examples:
 *   normalize_url('http://example.com//foo///bar') => 'http://example.com/foo/bar'
 *   normalize_url('//example.com//a')             => '//example.com/a'
 *   normalize_url('/some//relative//path')       => '/some/relative/path'
 *
 * @param string $url
 * @return string
 */
function normalize_url(string $url): string
{
    $original = $url;
    $parts = parse_url($url);

    // If parse_url fails, fall back to simple regex while protecting scheme.
    if ($parts === false) {
        if (preg_match('#^(https?://)#i', $url, $m)) {
            $prefix = $m[1];
            $rest = substr($url, strlen($prefix));
            $rest = preg_replace(pattern: '#/+#', replacement: '/', subject: $rest);
            return $prefix . $rest;
        }

        if (str_starts_with($url, '//')) {
            return '//' . preg_replace(pattern: '#/+#', replacement: '/', subject: substr(string: $url, offset: 2));
        }

        return preg_replace(pattern: '#/+#', replacement: '/', subject: $url);
    }

    $scheme   = $parts['scheme'] ?? null;
    $user     = $parts['user'] ?? null;
    $pass     = $parts['pass'] ?? null;
    $host     = $parts['host'] ?? null;
    $port     = $parts['port'] ?? null;
    $path     = $parts['path'] ?? '';
    $query    = $parts['query'] ?? null;
    $fragment = $parts['fragment'] ?? null;

    // Collapse multiple slashes in the path only.
    // This preserves a single leading slash (if any) and turns '///a//b' => '/a/b'.
    $path = preg_replace(pattern: '#/+#', replacement: '/', subject: $path);

    // Rebuild authority (user[:pass]@host[:port])
    $authority = '';
    if ($host !== null) {
        if ($user !== null) {
            $authority .= $user;
            if ($pass !== null) {
                $authority .= ':' . $pass;
            }
            $authority .= '@';
        }

        // For IPv6 host strings we ensure brackets when reconstructing authority.
        $hostOut = $host;
        if (
                str_contains($hostOut, ':')
                && $hostOut[0] !== '['
                && filter_var($hostOut, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
        ) {
            $hostOut = '[' . $hostOut . ']';
        }

        $authority .= $hostOut;
        if ($port !== null) {
            $authority .= ':' . $port;
        }
    }

    $result = '';

    if ($scheme !== null) {
        // Preserve scheme and "://"
        $result .= $scheme . '://';
        $result .= $authority;
    } elseif ($host !== null) {
        // protocol-relative or host-without-scheme: preserve leading //
        $result .= '//' . $authority;
    } elseif (str_starts_with($original, '//')) {
        // preserve protocol-relative leading //
        $result .= '//';
    }

    $result .= $path;

    if ($query !== null) {
        $result .= '?' . $query;
    }

    if ($fragment !== null) {
        $result .= '#' . $fragment;
    }

    return $result;
}

/**
 * Displays the returned translated text.
 *
 * @param string $string
 * @return string
 */
function trans(string $string): string
{
    return t__(msgid: $string, domain: config(key: 'app.locale_domain', default: 'codefy'));
}

/**
 * Escapes a translated string to make it safe for HTML output.
 *
 * @throws Exception
 */
function trans_html(string $string): string
{
    return esc_html__(string: $string, domain: config(key: 'app.locale_domain', default: 'codefy'));
}

/**
 * Escapes a translated string to make it safe for HTML attribute.
 *
 * @throws Exception
 */
function trans_attr(string $string): string
{
    return esc_attr__(string: $string, domain: config(key: 'app.locale_domain', default: 'codefy'));
}

/**
 * Returns the url of the application.
 */
function site_url(string $path = ''): string
{
    try {
        return normalize_url(Server::siteUrl($path));
    } catch (Exception $e) {
        error_log($e->getMessage());
    }

    return '';
}

/**
 * Queues an item.
 *
 * @param ShouldQueue $queue
 * @return NodeQueue
 */
function queue(ShouldQueue $queue): NodeQueue
{
    return new NodeQueue($queue);
}

/**
 * Return a new Gravatar Image instance.
 *
 * @param string|null $email
 * @return Image
 */
function gravatar(?string $email = null): Image
{
    return new Image($email);
}

/**
 * Return a new Gravatar Profile instance.
 *
 * @param string|null $email
 * @return Profile
 */
function gravatar_profile(?string $email = null): Profile
{
    return new Profile($email);
}

/**
 * Throw the given exception if the given condition is true.
 *
 * @param mixed $condition
 * @param string $exception
 * @param ...$parameters
 * @return mixed
 */
function throw_if(mixed $condition, string $exception = RuntimeException::class, ...$parameters): mixed
{
    if ($condition) {
        if (is_string($exception) && class_exists($exception)) {
            $exception = new $exception(...$parameters);
        }

        throw is_string($exception) ? new RuntimeException($exception) : $exception;
    }

    return $condition;
}

/**
 * Throw an HttpException with the given data.
 *
 * @param int $code
 * @param string|null $uri
 * @param string $message
 * @param Throwable|null $previous
 * @return never
 */
function abort(
    int $code = 500,
    ?string $uri = null,
    string $message = '',
    ?Throwable $previous = null
): never {
    throw HttpExceptionFactory::make(
        status: $code,
        uri: $uri,
        message: $message,
        previous: $previous
    );
}

/**
 * Abort (throw an HttpException) if the given condition is true.
 *
 * @param bool $condition
 * @param int $code
 * @param string $message
 * @param string|null $uri
 * @return void
 *
 */
function abort_if(
    bool $condition,
    int $code,
    ?string $uri = null,
    string $message = '',
): void {
    if ($condition) {
        abort(code: $code, uri: $uri, message: $message);
    }
}

/**
 * Abort (throw an HttpException) unless the given condition is true.
 *
 * @param bool $condition
 * @param int $code
 * @param string $message
 * @param string|null $uri
 * @return void
 *
 */
function abort_unless(
    bool $condition,
    int $code,
    ?string $uri = null,
    string $message = '',
): void {
    if (! $condition) {
        abort(code: $code, uri: $uri, message: $message);
    }
}

/**
 * @param array|string $template
 * @param array $data
 * @return ResponseInterface
 * @throws \Exception
 */
function view(array|string $template, array $data = []): ResponseInterface
{
    /** @var Renderer $view */
    $view = Codefy::$PHP->make(name: Renderer::class);

    return HtmlResponseFactory::create($view->render($template, $data));
}

/**
 * Returns the gate instance.
 *
 * @param string|null $permission
 * @param array $rules
 * @return Gate|bool|null
 */
function gate(?string $permission = null, array $rules = []): Gate|null|bool
{
    $request = RequestContext::get();

    if (! $request) {
        return null;
    }

    $auth = Codefy::$PHP->make(name: Gate::class);

    if (is_null__($permission)) {
        return $auth;
    }

    return $auth->can($permission, $rules);
}

/**
 * The authenticated user details.
 *
 * @throws ReflectionException
 * @throws TypeException
 */
function user(): object|bool|null
{
    return gate()?->current();
}

/**
 * Generate url's from named routes.
 *
 * @param string $name Name of the route.
 * @param array $params Data parameters.
 * @return string|null The url.
 * @throws NamedRouteNotFoundException
 * @throws RouteParamFailedConstraintException
 * @throws TooLateToAddNewRouteException
 */
function route(string $name, array $params = []): ?string
{
    $request = RequestContext::get();
    $routable = $request->getAttribute(RouteAttributes::ROUTE);
    if (null === $routable) {
        return null;
    }

    $router = Codefy::$PHP->router;
    $router->hydrateRoute($routable);

    return $router->url($name, $params);
}
