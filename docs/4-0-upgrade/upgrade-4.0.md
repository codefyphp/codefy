# Upgrading to 4.0

4.0 retains PHP 8.4 support and deliberately changes insecure or broken 3.x behavior. Review application integrations before upgrading a live installation.

## Deployment order

1. Back up application data and queue files. Stop queue workers and scheduler processes before replacing the framework. Do not run 3.x and 4.x workers against the same node files.
2. Update Composer dependencies.
3. Convert persistent jobs to `SerializableJob`, register their classes in `queue.jobs`, and re-enqueue pending work using trusted application data. The old JSON format did not contain enough information to reconstruct job classes safely; there is no automatic legacy-object fallback. See [queue migration](queues.md#migrating-3x-jobs).
4. Review the HTTP middleware order and configuration described below. Existing authentication cookies will be rejected, so users must sign in again. Deploy all application nodes together or route sessions to nodes running the same cookie format.
5. Review data passed from `validated()`, custom middleware aliases, transactional pipelines, and scheduler result handling. Run application tests, then restart workers with the new code and configuration.

## Behavior changes

| Area                  | 3.x behavior                                                               | 4.0 behavior / required action                                                                                                                                                      |
|-----------------------|----------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Auth cookies          | Encrypted bare tokens; browser expiry only                                 | Versioned encrypted token with a server-checked expiry. Legacy, expired, or malformed cookies are rejected. Keep positive `cookies.lifetime` and `cookies.remember` values.         |
| Auth failures         | Missing attributes or malformed cookie values could throw errors           | Guests are denied; stale cookies are replaced after successful authentication. Invalid encryption-key configuration still raises an error.                                          |
| CSRF tokens           | Time-derived SHA-1 tokens                                                  | New tokens contain 32 random bytes encoded as 64 hex characters. Valid existing encrypted alphanumeric CSRF tokens remain accepted. `csrf.salt` is no longer needed for generation. |
| CORS                  | Origin read from response; origins concatenated; no preflight handling     | Request-origin allowlist, one allowed origin, cache variation, and validated preflight responses. Credentialed wildcard configuration throws.                                       |
| Throttle identity     | Client CSRF header                                                         | `REMOTE_ADDR` by default, or an injected resolver using trusted identity. Forwarded headers are not used automatically.                                                             |
| Throttle response     | `401`                                                                      | `429`, `Retry-After`, and `Cache-Control: no-store`. Windows no longer extend with every request.                                                                                   |
| Validation            | `validated()` returned all input                                           | Only validation-engine accepted fields are returned. Add rules for fields the application needs.                                                                                    |
| FormDataRequest       | Validation callback could recurse indefinitely                             | Properties are hydrated directly from the successful validation result.                                                                                                             |
| Error responses       | Internal messages/class names could be exposed; HTML errors returned `200` | Production server messages are generic; JSON class names require debug mode; rendered HTML preserves error status.                                                                  |
| Error redirects       | Exception URI or Referer could send users off-site                         | Only local absolute paths are accepted. Absolute URLs, including same-origin URLs, fall back to `/`. Controller redirects remain an explicit application decision.                  |
| HTTPS detection       | Any client could supply proxy HTTPS headers                                | `Server::isSsl()` trusts native TLS indicators; forwarded headers require an explicit list of exact proxy IPs. Set `APP_BASE_URL` for URLs behind a proxy.                          |
| Middleware aliases    | Nested/static alias data and inconsistent precedence                       | Flat, instance-owned aliases; explicit callback aliases override configuration and defaults. Multiple aliases may point to the same class.                                          |
| Request context       | Static request survived handling                                           | `bind.request` restores an outer request or clears its binding in `finally`; Fiber bindings are isolated. Read the context during handling.                                         |
| Queue data            | JSON arrays treated as executable objects; old pending jobs deleted        | Explicit JSON payload restoration, owned leases, cumulative attempts, retained failed jobs, and serialized file updates.                                                            |
| Scheduler             | Exceptions leaked locks, shell failures looked successful                  | Locks release in `finally`; nonzero foreground exit codes are failures; `schedule:run` exits nonzero on recorded failures.                                                          |
| Overlap prevention    | Background process could outlive a released lock                           | `onlyOneInstance()` forces foreground execution. File locks are available for local workers.                                                                                        |
| PHP script scheduling | Paths unquoted; arguments duplicated or ignored                            | Script and interpreter paths are quoted, arguments included once, and nonexistent scripts rejected at registration.                                                                 |
| Transactions          | Operations went through a global query builder                             | Pipeline resolves `PDO` from its injected container and owns only the transaction it starts. An existing transaction is rejected without rolling it back.                           |
| RBAC                  | Circular inheritance accepted                                              | Adding a role or permission cycle throws `LogicException`, including when persisted graphs are loaded. Repair cyclic stored data.                                                   |
| Key generation        | Existing `.enc.key` overwritten                                            | Exclusive creation with private permissions; an existing file or symlink results in command failure. There is no implicit key rotation.                                             |

## Controller dependencies

`BaseController` no longer has a constructor or declares `$sessionService` and `$router`.
Remove calls to `parent::__construct()` targeting the former base constructor and inject
only the services each concrete controller uses or needs. Controllers that previously inherited
the constructor must now declare their own dependencies if they use those services.

A controller that only registers middleware needs no injected services:

```php
class AccountController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }
}
```

For a controller that needs a renderer, constructor injection can initialize the retained
protected `$view` property directly:

```php
class PageController extends BaseController
{
    public function __construct(protected \Qubus\View\Renderer $view)
    {
        $this->middleware('auth');
    }
}
```

The fluent `setView()` helper remains available for explicit setter injection. Initialize
`$view` through constructor or setter injection before reading it; it is no longer
automatically injected by an inherited constructor. The `redirect()` helper requires no
injected services.

## Configuration reminders

The dependency upgrades also require mail transport and SEO API migration. Configure `mailer.dsn` for Qubus Mail 6, replace PHPMailer exception catches with Symfony transport exceptions, and update sitemap/indexing factory calls for SEO v3. `CodefyMailer` now implements the mail interface through composition. See the [mail, logging, and SEO migration guide](dependency-upgrades.md) for complete examples and compatibility details.

Register CORS before middleware that rejects unauthenticated preflight requests. Keep CSRF token preparation before CSRF protection. Run authentication before session-cookie issuance. For HTTPS deployments set `cookies.secure` to `true`; configure cookie domain, path, and SameSite for the application. HTTP development environments may keep secure cookies disabled.

Existing weak password hashes still verify. On successful password verification, use `Password::needsRehash()` and replace the stored hash when necessary. The repository does not silently change application password records.

Cache-based scheduler mutex names have changed. Stop old workers and let old mutexes expire before starting new workers. The generic PSR-6 rate limiter and cache mutex do not provide distributed atomicity; see the component documents before deploying multiple workers or application nodes.
