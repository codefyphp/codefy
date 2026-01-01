<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Support\Traits\CollectionStackAware;

class DefaultMiddlewares
{
    use CollectionStackAware;

    public function __construct(array $collection = [])
    {
        $this->collection = $collection ?: [
            'api' => \Codefy\Framework\Http\Middleware\ApiMiddleware::class,
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'security.headers' => \Codefy\Framework\Http\Middleware\SecureHeaders\ContentSecurityPolicyMiddleware::class,
            'content.cache' => \Codefy\Framework\Http\Middleware\ContentCacheMiddleware::class,
            'cors' => \Codefy\Framework\Http\Middleware\CorsMiddleware::class,
            'csrf.token' => \Codefy\Framework\Http\Middleware\Csrf\CsrfTokenMiddleware::class,
            'csrf.protection' => \Codefy\Framework\Http\Middleware\Csrf\CsrfProtectionMiddleware::class,
            'css.minify' => \Codefy\Framework\Http\Middleware\CssMinifierMiddleware::class,
            'gate' => \Codefy\Framework\Http\Middleware\Auth\GateMiddleware::class,
            'honeypot' => \Codefy\Framework\Http\Middleware\Spam\HoneyPotMiddleware::class,
            'html.minify' => \Codefy\Framework\Http\Middleware\HtmlMinifierMiddleware::class,
            'http.cache' => \Codefy\Framework\Http\Middleware\Cache\CacheMiddleware::class,
            'http.cache.clear.data' => \Codefy\Framework\Http\Middleware\Cache\ClearSiteDataMiddleware::class,
            'http.cache.expires' => \Codefy\Framework\Http\Middleware\Cache\CacheExpiresMiddleware::class,
            'http.cache.prevention' => \Codefy\Framework\Http\Middleware\Cache\CachePreventionMiddleware::class,
            'js.minify' => \Codefy\Framework\Http\Middleware\JsMinifierMiddleware::class,
            'rate.limiter' => \Codefy\Framework\Http\Middleware\ThrottleMiddleware::class,
            'referrer.spam' => \Codefy\Framework\Http\Middleware\Spam\ReferrerSpamMiddleware::class,
            'user.authenticate' => \Codefy\Framework\Http\Middleware\Auth\AuthenticationMiddleware::class,
            'user.session' => \Codefy\Framework\Http\Middleware\Auth\UserSessionMiddleware::class,
            'user.authorization' => \Codefy\Framework\Http\Middleware\Auth\UserAuthorizationMiddleware::class,
            'user.session.expire' => \Codefy\Framework\Http\Middleware\Auth\ExpireUserSessionMiddleware::class,
            'php.debugbar' => \Codefy\Framework\Http\Middleware\DebugBarMiddleware::class,
            'http.exception' => \Codefy\Framework\Http\Middleware\Exception\HttpExceptionMiddleware::class,
            'html.http.exception' => \Codefy\Framework\Http\Middleware\Exception\HtmlHttpExceptionMiddleware::class,
            'json.http.exception' => \Codefy\Framework\Http\Middleware\Exception\JsonHttpExceptionMiddleware::class,
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'redirect.http.exception' => \Codefy\Framework\Http\Middleware\Exception\RedirectionHttpExceptionMiddleware::class,
            'bind.request' => \Codefy\Framework\Http\Middleware\BindRequestMiddleware::class,
            'user.cookie.decrypt' => \Codefy\Framework\Http\Middleware\Auth\UserCookieDecryptMiddleware::class,
        ];
    }
}
