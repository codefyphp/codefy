<?php

ini_set('session.save_path', sys_get_temp_dir());

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use Codefy\Framework\Application;
use Codefy\Framework\Http\Middleware\FirewallMiddleware;
use Codefy\Framework\Security\Firewall\BlockedResponseFactory;
use Codefy\Framework\Security\Firewall\FirewallExclusionPolicy;
use Codefy\Framework\Security\Firewall\NullThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatDetector;
use Codefy\Framework\Security\Firewall\ThreatPatternRegistry;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Uri\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Config\ConfigContainer;

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function codefy(): Application
{
    $app = require(dirname(__DIR__) . '/bootstrap/app.php');
    return $app;
}

function firewall_config(array $overrides = []): ConfigContainer
{
    $defaults = [
        'enabled' => true,
        'block' => true,
        'alert_min_severity' => 'high',
        'notifiers' => [],
        'ignored_paths' => [],
    ];

    return codefy()->configContainer->setConfigKey('firewall', array_replace_recursive($defaults, $overrides));
}

function firewall_request(
    string $method = 'GET',
    string $uri = '/'
): ServerRequestInterface {
    $baseRequest = codefy()->request;

    $request = $baseRequest
        ->withMethod($method)
        ->withUri($baseRequest->getUri()->withPath('/')->withQuery(''));

    $parts = parse_url($uri);

    if (isset($parts['path'])) {
        $request = $request->withUri(
            $request->getUri()->withPath($parts['path'])
        );
    }

    if (isset($parts['query'])) {
        $request = $request->withUri(
            $request->getUri()->withQuery($parts['query'])
        );
    }

    return $request;
}

/**
 * @param array<string, mixed> $config
 */
function firewall_middleware(
    array $config = []
): FirewallMiddleware {
    $configContainer = firewall_config([
        'firewall' => array_replace_recursive(
            [
                'enabled' => true,
                'block' => false,
                'log_payload' => false,
                'alert_min_severity' => 'high',
                'notifiers' => [],
                'ignored_paths' => [],
            ],
            $config
        ),
    ]);

    $patternRegistry = new ThreatPatternRegistry(
        config: $configContainer
    );

    $detector = new ThreatDetector(
        registry: $patternRegistry,
        exclusionPolicy: new FirewallExclusionPolicy(config: $configContainer),
        threatLogger: new NullThreatLogger()
    );

    return new FirewallMiddleware(
        detector: $detector,
        logger: new NullThreatLogger(),
        blockedResponseFactory: new BlockedResponseFactory(),
        config: $configContainer
    );
}

function middleware_request(
    string $method = 'GET',
    string $path = '/',
    array $query = [],
    array|object|null $parsedBody = null,
    array $headers = [],
    array $serverParams = [],
): ServerRequestInterface {
    $method = strtoupper($method);

    $serverParams = array_replace(
        [
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $path,
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'HTTPS' => 'off',
        ],
        $serverParams
    );

    $bodyResource = fopen('php://temp', 'r+');

    if ($bodyResource === false) {
        throw new RuntimeException(
            'Unable to create the test request body stream.'
        );
    }

    $request = new ServerRequest(
        serverParams: $serverParams,
        uploadedFiles: [],
        uri: new Uri('http://localhost' . normalize_test_path($path)),
        method: $method,
        body: new Stream($bodyResource),
        headers: $headers
    );

    if ($query !== []) {
        $request = $request->withQueryParams($query);
    }

    if ($parsedBody !== null) {
        $request = $request->withParsedBody($parsedBody);
    }

    return $request;
}

function normalize_test_path(string $path): string
{
    if ($path === '') {
        return '/';
    }

    return str_starts_with($path, '/')
    ? $path
    : '/' . $path;
}
