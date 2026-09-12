<?php

declare(strict_types=1);

use Codefy\Framework\Auth\UserSession;
use Codefy\Framework\Http\Middleware\Auth\AuthenticationMiddleware;
use Codefy\Framework\Http\Middleware\Auth\UserAuthorizationMiddleware;
use Codefy\Framework\Http\Middleware\Auth\UserCookieDecryptMiddleware;
use Codefy\Framework\Http\Middleware\Auth\UserSessionMiddleware;
use Codefy\Framework\Tests\Security\Fixtures\FakeRequestHandler;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Qubus\Http\Response;
use Laminas\Diactoros\ResponseFactory;

beforeEach(function () {
    $this->app = codefy();
    $this->key = Key::createNewRandomKey();
    $this->app->configContainer->setConfigKey('app', ['crypto_key' => $this->key->saveToAsciiSafeString()]);
    $this->app->configContainer->setConfigKey('auth', ['cookie_name' => 'USERSESSID', 'redirect_guests_to' => '/login']);
});

it('rejects absent and malformed auth cookies without server errors', function (mixed $cookie) {
    $middleware = new UserAuthorizationMiddleware($this->app->configContainer, new ResponseFactory());
    $request = middleware_request()->withCookieParams(['USERSESSID' => $cookie])
        ->withAttribute(AuthenticationMiddleware::AUTH_ATTRIBUTE, new UserSession()->withToken('valid'));
    $handler = new FakeRequestHandler(new Response());
    $response = $middleware->process($request, $handler);
    expect($response->getStatusCode())->toBe(302)->and($response->getHeaderLine('Location'))->toBe('/login')
        ->and($handler->calls)->toBe(0);
})->with([null, '', 'forged', [['array']]]);

it('requires user details as well as a valid encrypted cookie', function () {
    $middleware = new UserAuthorizationMiddleware($this->app->configContainer, new ResponseFactory());
    $request = middleware_request()->withCookieParams(['USERSESSID' => Crypto::encrypt(json_encode(['version' => 1, 'token' => 'valid', 'expires' => time() + 60]), $this->key)]);
    $handler = new FakeRequestHandler(new Response());
    expect($middleware->process($request, $handler)->getStatusCode())->toBe(302);
    $middleware->process($request->withAttribute(AuthenticationMiddleware::AUTH_ATTRIBUTE, new UserSession()->withToken('valid')), $handler);
    expect($handler->calls)->toBe(1);
});

it('replaces stale auth cookies after successful authentication', function (mixed $cookie) {
    $middleware = new UserSessionMiddleware($this->app->configContainer, $this->app->httpCookie);
    $request = middleware_request()->withCookieParams(['USERSESSID' => $cookie])
        ->withAttribute(AuthenticationMiddleware::AUTH_ATTRIBUTE, new UserSession()->withToken('new-user'));
    $response = $middleware->process($request, new FakeRequestHandler(new Response()));
    expect($response->getHeaderLine('Set-Cookie'))->toContain('USERSESSID=');
})->with(['malformed', [['array']]]);

it('removes stale decrypted attributes when no valid cookie is supplied', function () {
    $handler = new FakeRequestHandler(new Response());
    new UserCookieDecryptMiddleware($this->app->configContainer)->process(
        middleware_request()->withAttribute(UserCookieDecryptMiddleware::USER_COOKIE, 'old-user'), $handler
    );
    expect($handler->lastRequest->getAttribute(UserCookieDecryptMiddleware::USER_COOKIE))->toBeNull();
});

it('clears sessions without uninitializing typed properties', function () {
    $session = new UserSession()->withToken('token');
    $session->clear();
    $session->clear();
    expect($session->token)->toBeNull()->and($session->isEmpty())->toBeTrue();
});

it('rejects replayed cookies after their server enforced expiry', function () {
    $handler = new FakeRequestHandler(new Response());
    $cookie = Crypto::encrypt(json_encode(['version' => 1, 'token' => 'valid', 'expires' => time() - 1]), $this->key);
    new UserCookieDecryptMiddleware($this->app->configContainer)->process(
        middleware_request()->withCookieParams(['USERSESSID' => $cookie]), $handler
    );
    expect($handler->lastRequest->getAttribute(UserCookieDecryptMiddleware::USER_COOKIE))->toBeNull();
});

it('rejects legacy cookies without an authenticated expiry', function () {
    $handler = new FakeRequestHandler(new Response());
    new UserCookieDecryptMiddleware($this->app->configContainer)->process(
        middleware_request()->withCookieParams(['USERSESSID' => Crypto::encrypt('valid', $this->key)]), $handler
    );
    expect($handler->lastRequest->getAttribute(UserCookieDecryptMiddleware::USER_COOKIE))->toBeNull();
});

it('round trips issued cookies through decryption and authorization', function () {
    $user = new UserSession()->withToken('issued-user');
    $request = middleware_request()->withAttribute(AuthenticationMiddleware::AUTH_ATTRIBUTE, $user);
    $response = new UserSessionMiddleware($this->app->configContainer, $this->app->httpCookie)
        ->process($request, new FakeRequestHandler(new Response()));
    $cookie = \Qubus\Http\Cookies\CookiesResponse::get($response, 'USERSESSID')->getValue();
    $request = $request->withCookieParams(['USERSESSID' => $cookie]);
    $handler = new FakeRequestHandler(new Response());
    new UserCookieDecryptMiddleware($this->app->configContainer)->process($request, $handler);
    expect($handler->lastRequest->getAttribute(UserCookieDecryptMiddleware::USER_COOKIE))->toBe('issued-user');
    $authorization = new UserAuthorizationMiddleware($this->app->configContainer, new ResponseFactory());
    expect($authorization->process($request, $handler)->getStatusCode())->toBe(200);
    $renewed = new UserSessionMiddleware($this->app->configContainer, $this->app->httpCookie)
        ->process($request, new FakeRequestHandler(new Response()));
    expect($renewed->hasHeader('Set-Cookie'))->toBeFalse();
});
