<?php

declare(strict_types=1);

use Codefy\Framework\Http\Middleware\CorsMiddleware;
use Codefy\Framework\Tests\Security\Fixtures\FakeRequestHandler;
use Qubus\Config\Collection;
use Qubus\Http\Response;

function cors_test_middleware(array $overrides = []): CorsMiddleware
{
    $config = new Collection([]);
    $config->setConfigKey('cors', array_replace(require dirname(__DIR__, 2) . '/config/cors.php', $overrides));
    return new CorsMiddleware($config);
}

it('reflects one allowed request origin and preserves Vary', function () {
    $middleware = cors_test_middleware(['access-control-allow-origin' => ['https://one.test', 'https://two.test']]);
    $handler = new FakeRequestHandler(new Response(headers: ['Vary' => 'Accept-Encoding']));
    $response = $middleware->process(middleware_request(headers: ['Origin' => 'https://two.test']), $handler);
    expect($response->getHeaderLine('Access-Control-Allow-Origin'))->toBe('https://two.test')
        ->and($response->getHeaderLine('Vary'))->toContain('Accept-Encoding')->toContain('Origin');
});

it('handles valid preflight without executing the application', function () {
    $handler = new FakeRequestHandler(new Response());
    $response = cors_test_middleware()->process(middleware_request(method: 'OPTIONS', headers: [
        'Origin' => 'https://client.test', 'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type, x-csrf-token',
    ]), $handler);
    expect($response->getStatusCode())->toBe(204)->and($handler->calls)->toBe(0)
        ->and($response->getHeaderLine('Access-Control-Allow-Origin'))->toBe('*')
        ->and($response->hasHeader('Access-Control-Allow-Credentials'))->toBeFalse();
});

it('denies unapproved preflight methods and headers', function (array $headers) {
    $handler = new FakeRequestHandler(new Response());
    $response = cors_test_middleware()->process(middleware_request(method: 'OPTIONS', headers: array_replace([
        'Origin' => 'https://client.test', 'Access-Control-Request-Method' => 'POST',
    ], $headers)), $handler);
    expect($response->getStatusCode())->toBe(403)->and($handler->calls)->toBe(0)
        ->and($response->hasHeader('Access-Control-Allow-Origin'))->toBeFalse();
})->with([[['Access-Control-Request-Method' => 'TRACE']], [['Access-Control-Request-Headers' => 'X-Unapproved']]]);

it('removes conflicting downstream CORS grants for denied or absent origins', function (array $headers) {
    $handler = new FakeRequestHandler(new Response(headers: ['Access-Control-Allow-Origin' => '*']));
    $response = cors_test_middleware(['access-control-allow-origin' => ['https://allowed.test']])
        ->process(middleware_request(headers: $headers), $handler);
    expect($response->hasHeader('Access-Control-Allow-Origin'))->toBeFalse()->and($handler->calls)->toBe(1);
})->with([[[]], [['Origin' => 'https://evil.test']]]);

it('rejects credentials with wildcard origins', function () {
    cors_test_middleware(['access-control-allow-credentials' => true])->process(
        middleware_request(headers: ['Origin' => 'https://client.test']), new FakeRequestHandler(new Response())
    );
})->throws(InvalidArgumentException::class);

it('supports credentials with an explicit allowlist', function () {
    $response = cors_test_middleware([
        'access-control-allow-credentials' => true, 'access-control-allow-origin' => ['https://client.test'],
    ])->process(middleware_request(headers: ['Origin' => 'https://client.test']), new FakeRequestHandler(new Response()));
    expect($response->getHeaderLine('Access-Control-Allow-Credentials'))->toBe('true');
});
