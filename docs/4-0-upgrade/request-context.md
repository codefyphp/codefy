# Request context and middleware configuration

## Scoped request binding

`BindRequestMiddleware` binds the request while its downstream handler executes. It restores a previous binding after a nested call or clears the binding after the outermost call, including on exceptions.

```php
use Codefy\Framework\Http\RequestContext;

if (RequestContext::has()) {
    $request = RequestContext::get();
}
```

`get()` without an active binding throws `LogicException` with an explicit message. Code outside middleware can use `set()` and `clear()`, but must own its cleanup. Middleware or controllers should read the bound request during handling, not after the response has completed.

Native PHP Fibers use separate weakly referenced request bindings. A newly created Fiber does not inherit the main execution context's request. Ended Fibers do not remain retained by the context store. This change does not make all framework/application singletons safe for concurrent requests: the Application singleton, CSRF helper's static current middleware, and third-party services still need lifecycle review. Swoole coroutine isolation is not asserted by the native Fiber implementation.

## Middleware aliases

`Middleware::alias()` merges a flat name-to-class map into that instance. Alias registration no longer nests arrays or leaks through static state into another Middleware object.

```php
$builder->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'cors' => AppCorsMiddleware::class,
        'public-cors' => AppCorsMiddleware::class,
    ]);
});
```

Precedence is framework defaults, then existing application configuration, then aliases supplied to this callback. Two names can intentionally resolve to the same class; values are no longer deduplicated. Repeated registration of a name in one instance replaces that name's earlier class.
