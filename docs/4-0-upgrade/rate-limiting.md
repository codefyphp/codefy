# Rate limiting

`ThrottleMiddleware` registers its condition once when constructed, so the same middleware instance can handle repeated requests. Configuration defaults to 60 requests in 60 seconds:

```php
return ['ttl' => 60, 'max_attempts' => 60]; // config/throttle.php
```

The default identifier is the connection's `REMOTE_ADDR`. Changing `X-CSRF-Token`, `X-Forwarded-For`, or another client header does not change this identifier. Missing remote-address information uses a shared `unknown` bucket. Behind a proxy, supply an explicit resolver if the application has already established a trusted client identity.

```php
$middleware = new ThrottleMiddleware(
    $config,
    new RateLimiter($cachePool),
    identifierResolver: static function (ServerRequestInterface $request): string {
        // Set this attribute only after verifying authentication.
        return 'account:' . $request->getAttribute('authenticated_account_id');
    },
);
```

The resolver must return a nonempty string. Do not return an unverified request header. Use a separate limiter instance/cache namespace for each independent policy; sharing identifiers and TTLs in one cache shares counters. NAT clients share the default address bucket.

## Window semantics

`Condition` requires positive TTL and limit values. `increment()` requires a positive count. Every configured window receives the increment, even if an earlier window is exceeded. A window's expiration is anchored to its first request, not pushed forward by later requests. Expired or invalid cached interval data starts a new interval. Failed cache saves raise `RuntimeException` rather than silently allowing uncounted traffic.

```php
$limiter = new RateLimiter($cachePool);
$limiter->add(new Condition(ttl: 10, limit: 5));
$limiter->add(new Condition(ttl: 60, limit: 20));
$limiter->increment('account:42');
$intervals = $limiter->getIntervals('account:42');
$limiter->reset('account:42');
```

Duplicate TTLs and conditions dominated by an existing condition remain invalid. `Interval::expiresAt` exposes a Unix expiration timestamp; its constructor's first argument remains a relative lifetime in seconds.

A rejected request receives HTTP `429`, a numeric `Retry-After` in seconds, and `Cache-Control: no-store`. `RateException::$retryAfter` reports the largest remaining wait among exceeded windows. Rejected attempts count toward every window. The downstream handler is not called.

## Concurrency limits

PSR-6 has no atomic increment or compare-and-swap operation. This implementation performs a read, increment, and save; concurrent requests may lose increments. It is suitable for best-effort application throttling, not an exact distributed quota or the sole protection for a high-risk endpoint. Use an atomic gateway/backend limiter for strict multiworker enforcement. Cache storage must be shared when you want counters shared between workers, and an in-memory test cache does not provide that sharing.
