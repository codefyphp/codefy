<?php

declare(strict_types=1);

use Codefy\Framework\Application;
use Codefy\Framework\Http\Middleware\Csrf\CsrfProtectionMiddleware;
use Codefy\Framework\Http\Middleware\Csrf\CsrfTokenMiddleware;
use Codefy\Framework\Http\Middleware\Csrf\TokenMismatchException;
use Codefy\Framework\Tests\Security\Fixtures\FakeRequestHandler;
use Defuse\Crypto\Key;
use Qubus\Http\Response;

$csrfMiddlewareApp = function (): Application {
    $app = codefy();
    $app->configContainer->setConfigKey('app', [
        'crypto_key' => Key::createNewRandomKey()->saveToAsciiSafeString(),
    ]);
    $app->configContainer->setConfigKey('csrf', [
        'header' => 'X-CSRF-Token',
        'request_header' => true,
        'csrf_token' => '_token',
        'salt' => 'csrf-test-salt',
        'cookie_name' => 'CSRFSESSID',
        'lifetime' => 3600,
    ]);

    return $app;
};

it('keeps the cookie token independent from a submitted header', function () use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $expected = str_repeat('a', 40);
    $provided = str_repeat('b', 40);

    $middleware = new class ($app->configContainer, $app->httpCookie) extends CsrfTokenMiddleware {
        public function encryptToken(string $token): string
        {
            return $this->sign($token);
        }
    };
    $handler = new FakeRequestHandler(new Response());
    $request = middleware_request(
        method: 'POST',
        headers: ['X-CSRF-Token' => $provided],
        serverParams: ['HTTP_REFERER' => '/form']
    )->withCookieParams([
        'CSRFSESSID' => $middleware->encryptToken($expected),
    ]);

    $middleware->process($request, $handler);

    expect($handler->lastRequest?->getAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE))
        ->toBe($expected)
        ->and($handler->lastRequest?->getHeaderLine('X-CSRF-Token'))
        ->toBe($expected)
        ->and($handler->lastRequest?->getAttribute(CsrfTokenMiddleware::CSRF_SUBMITTED_HEADER_ATTRIBUTE))
        ->toBe($provided);

    $protection = $app->make(name: CsrfProtectionMiddleware::class);

    expect(fn () => $protection->process($handler->lastRequest, new FakeRequestHandler(new Response())))
        ->toThrow(TokenMismatchException::class);
});

it('provides the legacy header without marking it as client submitted', function () use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $middleware = $app->make(name: CsrfTokenMiddleware::class);
    $handler = new FakeRequestHandler(new Response());

    $middleware->process(
        middleware_request(method: 'GET'),
        $handler
    );

    $expected = $handler->lastRequest?->getAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE);

    expect($expected)->toBeString()->not->toBeEmpty()
        ->and($handler->lastRequest?->getHeaderLine('X-CSRF-Token'))->toBe($expected)
        ->and($handler->lastRequest?->getAttribute(CsrfTokenMiddleware::CSRF_SUBMITTED_HEADER_ATTRIBUTE))
        ->toBe('');
});

it('rejects a protected request with no submitted token', function () use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $tokenMiddleware = $app->make(name: CsrfTokenMiddleware::class);
    $handler = new FakeRequestHandler(new Response());

    $tokenMiddleware->process(middleware_request(
        method: 'POST',
        serverParams: ['HTTP_REFERER' => '/form']
    ), $handler);

    expect($handler->lastRequest?->hasHeader('X-CSRF-Token'))->toBeTrue();

    $protection = $app->make(name: CsrfProtectionMiddleware::class);

    expect(fn () => $protection->process($handler->lastRequest, new FakeRequestHandler(new Response())))
        ->toThrow(TokenMismatchException::class);
});

it('rejects a protected request with a forged submitted header', function () use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $middleware = $app->make(name: CsrfProtectionMiddleware::class);
    $request = middleware_request(
        method: 'POST',
        headers: ['X-CSRF-Token' => str_repeat('b', 40)],
        serverParams: ['HTTP_REFERER' => '/form']
    )->withAttribute(
        CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE,
        str_repeat('a', 40)
    );

    expect(fn () => $middleware->process($request, new FakeRequestHandler(new Response())))
        ->toThrow(TokenMismatchException::class);
});

it('accepts a matching submitted header', function () use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $middleware = $app->make(name: CsrfProtectionMiddleware::class);
    $handler = new FakeRequestHandler(new Response());
    $token = str_repeat('a', 40);
    $request = middleware_request(
        method: 'POST',
        headers: ['X-CSRF-Token' => $token],
        serverParams: ['HTTP_REFERER' => '/form']
    )->withAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE, $token);

    $middleware->process($request, $handler);

    expect($handler->calls)->toBe(1);
});

it('accepts a matching configured form field', function () use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $middleware = $app->make(name: CsrfProtectionMiddleware::class);
    $handler = new FakeRequestHandler(new Response());
    $token = str_repeat('a', 40);
    $request = middleware_request(
        method: 'POST',
        headers: ['X-CSRF-Token' => $token],
        parsedBody: ['_token' => $token],
        serverParams: ['HTTP_REFERER' => '/form']
    )
        ->withAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE, $token)
        ->withAttribute(CsrfTokenMiddleware::CSRF_SUBMITTED_HEADER_ATTRIBUTE, '');

    $middleware->process($request, $handler);

    expect($handler->calls)->toBe(1);
});

it('replaces malformed CSRF cookies with a random token', function (mixed $cookie) use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $middleware = new CsrfTokenMiddleware($app->configContainer, $app->httpCookie);
    $handler = new FakeRequestHandler(new Response());
    $response = $middleware->process(middleware_request()->withCookieParams(['CSRFSESSID' => $cookie]), $handler);
    expect($handler->lastRequest->getAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE))->toMatch('/^[a-f0-9]{64}$/')
        ->and($response->getHeaderLine('Set-Cookie'))->toContain('CSRFSESSID=');
})->with(['invalid', [['array']]]);

it('rejects CSRF failures without requiring a Referer', function () use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $request = middleware_request(method: 'POST')->withAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE, 'expected');
    expect(fn () => new CsrfProtectionMiddleware($app->configContainer, $app->httpCookie)
        ->process($request, new FakeRequestHandler(new Response())))->toThrow(TokenMismatchException::class);
});

it('does not refresh an existing cookie merely because a previous request was new', function () use ($csrfMiddlewareApp) {
    $app = $csrfMiddlewareApp();
    $middleware = new CsrfTokenMiddleware($app->configContainer, $app->httpCookie);
    $handler = new FakeRequestHandler(new Response());
    $middleware->process(middleware_request(), $handler);
    $token = $handler->lastRequest->getAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE);
    $signed = \Defuse\Crypto\Crypto::encrypt($token, Key::loadFromAsciiSafeString($app->configContainer->getConfigKey('app.crypto_key')));
    $response = $middleware->process(middleware_request()->withCookieParams(['CSRFSESSID' => $signed]), $handler);
    expect($response->hasHeader('Set-Cookie'))->toBeFalse();
});
