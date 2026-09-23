# HTTP security in 4.0

## CORS

`CorsMiddleware` reads the request's `Origin`. An allowlist response contains the single matching origin, not the complete list. Noncredentialed wildcard policies return `*`. Boolean and existing array-style credential configuration are supported; credentials with a wildcard origin throw `InvalidArgumentException`.

Example `config/cors.php`:

```php
return [
    'access-control-allow-origin' => ['https://app.example.com'],
    'access-control-allow-credentials' => true,
    'access-control-allow-methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'access-control-allow-headers' => ['Content-Type', 'Authorization', 'X-CSRF-Token'],
    'access-control-expose-headers' => ['X-Request-Id'],
    'access-control-max-age' => [3600],
];
```

An `OPTIONS` request with `Origin` and `Access-Control-Request-Method` is a preflight. Allowed methods and requested headers are checked; header names are compared case-insensitively. List methods and headers explicitly. Valid preflights return an empty `204`, denied preflights return `403`, and neither executes the downstream handler. Ordinary `OPTIONS` requests still reach the application.

Responses vary by `Origin`; preflight responses also vary by requested method and headers. Existing `Vary` values are preserved. This middleware replaces downstream CORS grants so that another middleware cannot accidentally broaden its configured policy. Requests without an allowed origin receive no CORS grant. Non-preflight requests still execute normally: CORS controls browser response access and does not replace authorization or CSRF protection.

The implementation follows the [Fetch CORS protocol](https://fetch.spec.whatwg.org/#http-cors-protocol), including the restriction on credentialed wildcard origins.

## CSRF

The pipeline order is `csrf.token` followed by `csrf.protection`, before the protected handler. Token preparation stores the expected plaintext token in `CSRF_TOKEN` and preserves the submitted header separately in `CSRF_SUBMITTED_HEADER_TOKEN`. The optional legacy request header remains available downstream, but is never treated as proof that the client submitted the token.

New tokens are `bin2hex(random_bytes(32))`. The cookie is authenticated and encrypted with the configured application key. A malformed cookie, nonstring cookie value, or invalid decrypted token causes a replacement token to be prepared. An unsafe request still needs a matching submitted header or configured form field; replacing a malformed cookie does not make the request valid. Token comparison uses `hash_equals()`.

The default form field is `_token`; configure `csrf.csrf_token` and `csrf.header` as before. Hidden-field output escapes HTML attributes. CSRF failures no longer require a Referer and use `/` as their error redirect target. A reused token middleware instance no longer refreshes valid cookies just because an earlier request created one. Token-cookie lifetime still controls browser retention; CSRF cookies are not authentication credentials.

## Authentication cookies

Login form credentials must be nonempty strings in an array parsed body. Malformed credentials return no authenticated session rather than being passed into the repository as arrays or objects.

Authentication-cookie plaintext is now a JSON envelope containing `version: 1`, a nonempty `token`, and integer `expires`. The complete envelope is encrypted and authenticated. The server rejects expiry at or before the current time, even if a client manually resends the cookie. Cookie lifetime is fixed at issuance; reading an existing valid cookie does not extend its server expiry.

`UserSessionMiddleware` issues the envelope after successful authentication. It uses `cookies.lifetime` (default 3600 seconds), or `cookies.remember` (default 2592000 seconds) for a form containing `rememberme=yes`. Both must be positive. Existing valid matching cookies are preserved; malformed, expired, or mismatched cookies are replaced. A missing or empty authenticated user token is an integration error and does not issue a cookie.

`UserCookieDecryptMiddleware` removes any preexisting `auth.token` attribute before processing the current cookie. It adds the attribute only after successful validation. `UserAuthorizationMiddleware` requires authenticated user details and a matching, unexpired cookie. Malformed ciphertext is an authentication failure; a bad application encryption key is an operational error and is not silently treated as a guest.

All 3.x bare-token cookies are invalidated. Custom cookie integrations should use the same envelope contract or integrate through the supplied middleware. Maintain synchronized clocks across application servers. Browser cookie deletion is not server-side revocation: applications needing immediate logout revocation must invalidate their repository token or server-side session. UserSession and CsrfSession clearing now leaves their typed properties initialized to `null`.

## Error handling and redirects

Production JSON strategies hide server exception messages and class names. Framework HTTP exceptions in the `400–499` range may expose their intended client-facing message; do not place secrets in these messages. Debug mode permits detailed exception information. The strategy middleware passes the original exception to its handler so logging retains the original cause.

Error redirects accept local paths such as `/form?retry=1`. Off-site URLs, scheme-relative URLs, backslashes, control characters, and redirect loops fall back to `/`. The explicit exception URI is checked as well as Referer. This restriction is specific to error middleware; application controller redirects still support intentionally selected external destinations.

Rendered HTML errors preserve the HTTP error status. Swoole catch responses use a generic internal-error body. Swoole integration was inspected but not exercised against a running Swoole server during this review.

## Firewall and proxy handling

Threat extraction now includes headers (source `header`, lowercased field names) and cookies (source `cookie`). Existing per-rule source restrictions still apply, so SSRF rules do not suddenly inspect browser version strings in User-Agent. Enable additional sources explicitly, for example `firewall.rules.xss.sources = ['query', 'body', 'header', 'cookie']`. Query fields are no longer extracted twice.

The detector is a pattern filter, not a substitute for parameterized SQL, safe output encoding, URL validation, or request-size limits. It does not parse raw JSON bodies itself; provide parsed bodies before detection. Keep payload logging disabled for sensitive inputs unless the application has an appropriate redaction policy.

`Server::isSsl()` ignores forwarded headers unless its caller passes the connecting proxy's exact address:

```php
$isHttps = Server::isSsl(['192.0.2.10', '2001:db8::10']);
```

This API accepts exact IPs, not CIDR ranges. Trusted proxies must overwrite forwarded headers. For public URL generation through `Server::siteUrl()`, configure a trusted `APP_BASE_URL`, since that helper does not supply a proxy allowlist.
