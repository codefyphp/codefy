<?php

declare(strict_types=1);

use Codefy\Framework\Http\Middleware\BindRequestMiddleware;
use Codefy\Framework\Http\RequestContext;
use Codefy\Framework\Tests\Http\FakeHandler;

it('cleans up request context when handling fails', function () {
    RequestContext::clear();
    expect(fn () => new BindRequestMiddleware()->process(middleware_request(), new FakeHandler(new RuntimeException('failure'))))
        ->toThrow(RuntimeException::class);
    expect(RequestContext::has())->toBeFalse();
    expect(fn () => RequestContext::get())->toThrow(LogicException::class);
});

it('isolates overlapping fibers and restores the outer request context', function () {
    $outer = middleware_request(path: '/outer');
    RequestContext::set($outer);
    $fiber = new Fiber(function () {
        expect(RequestContext::has())->toBeFalse();
        RequestContext::set(middleware_request(path: '/fiber'));
        Fiber::suspend();
        expect(RequestContext::get()->getUri()->getPath())->toBe('/fiber');
        RequestContext::clear();
    });
    $fiber->start();
    expect(RequestContext::get())->toBe($outer);
    $fiber->resume();
    RequestContext::clear();
});
