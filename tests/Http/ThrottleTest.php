<?php

declare(strict_types=1);

use Codefy\Framework\Http\Middleware\ThrottleMiddleware;
use Codefy\Framework\Http\Throttle\Condition;
use Codefy\Framework\Http\Throttle\Interval;
use Codefy\Framework\Http\Throttle\RateException;
use Codefy\Framework\Http\Throttle\RateLimiter;
use Codefy\Framework\Tests\Security\Fixtures\FakeRequestHandler;
use Qubus\Cache\InMemoryCache;
use Qubus\Config\Collection;
use Qubus\Http\Response;

it('cannot bypass throttling by rotating client headers and supports repeated requests', function () {
    $config = new Collection([]);
    $config->setConfigKey('throttle', ['ttl' => 60, 'max_attempts' => 1]);
    $middleware = new ThrottleMiddleware($config, new RateLimiter(new InMemoryCache()));
    $handler = new FakeRequestHandler(new Response());
    $request = middleware_request(headers: ['X-CSRF-Token' => 'one']);
    expect($middleware->process($request, $handler)->getStatusCode())->toBe(200);
    $response = $middleware->process($request->withHeader('X-CSRF-Token', 'two')->withHeader('X-Forwarded-For', '1.2.3.4'), $handler);
    expect($response->getStatusCode())->toBe(429)->and($handler->calls)->toBe(1)
        ->and((int) $response->getHeaderLine('Retry-After'))->toBeGreaterThan(0);
    expect($middleware->process(middleware_request(serverParams: ['REMOTE_ADDR' => '127.0.0.2']), $handler)->getStatusCode())
        ->toBe(200);
});

it('counts every window even when an earlier condition is exceeded', function () {
    $limiter = new RateLimiter(new InMemoryCache());
    $limiter->add(new Condition(10, 1))->add(new Condition(60, 2));
    $limiter->increment('client');
    expect(fn () => $limiter->increment('client'))->toThrow(RateException::class);
    expect($limiter->getIntervals('client')[1]->count)->toBe(2);
    $limiter->reset('client');
    expect($limiter->getIntervals('client')[0]->count)->toBe(0);
});

it('keeps a fixed expiry instead of extending it on every request', function () {
    $cache = new InMemoryCache();
    $limiter = new RateLimiter($cache);
    $limiter->add(new Condition(60, 5));
    $interval = new Interval(10, 1);
    $item = $cache->getItem(md5('client-60'))->set($interval)->expiresAfter(10);
    $cache->save($item);
    $limiter->increment('client');
    expect($limiter->getIntervals('client')[0]->expiresAt)->toBe($interval->expiresAt);
});

it('validates positive rate limit conditions', function (int $ttl, int $limit) {
    new Condition($ttl, $limit);
})->with([[0, 1], [1, 0], [-1, 1]])->throws(InvalidArgumentException::class);

it('rejects negative counter increments', function () {
    new RateLimiter(new InMemoryCache())->increment('client', -1);
})->throws(InvalidArgumentException::class);
