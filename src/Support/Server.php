<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\env;
use function Qubus\Routing\Helpers\request;
use function Qubus\Security\Helpers\esc_url;
use function Qubus\Support\Helpers\add_trailing_slash;
use function Qubus\Support\Helpers\concat_ws;
use function strtolower;

class Server
{
    private function __construct()
    {
        // Prevent instantiation
    }

    public static function isSsl(): bool
    {
        if (
            isset($_SERVER['HTTPS'])
                && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1')
        ) {
            return true;
        }

        if (
            isset($_SERVER['SERVER_PORT'])
                && (int)$_SERVER['SERVER_PORT'] === 443
        ) {
            return true;
        }

        // Handle reverse proxy / load balancer headers
        if (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
        ) {
            return true;
        }

        if (
            isset($_SERVER['HTTP_X_FORWARDED_SSL'])
                && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on'
        ) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public static function siteUrl(string $path = ''): string
    {
        $scheme = (static::isSsl() ? 'https://' : 'http://');

        $url = env(key: 'APP_BASE_URL') ?? $scheme . request()->getHost();
        $url = add_trailing_slash($url);

        $url = concat_ws(string1: $url, string2: $path, separator: '');

        return esc_url(url: $url);
    }
}
