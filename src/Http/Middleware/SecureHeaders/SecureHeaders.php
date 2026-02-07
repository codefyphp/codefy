<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\SecureHeaders;

use Codefy\Framework\Application;
use ParagonIE\CSPBuilder\CSPBuilder;

use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_merge;
use function array_search;
use function array_unshift;
use function array_values;
use function base64_encode;
use function bin2hex;
use function filter_var;
use function filter_var_array;
use function implode;
use function intval;
use function max;
use function random_bytes;
use function sprintf;

use const FILTER_VALIDATE_URL;

final class SecureHeaders
{
    /**
     * @var bool
     */
    protected bool $compiled = false;

    /**
     * @var array
     */
    protected array $headers = [];

    protected static array $nonces = [
        'script' => [],
        'style' => [],
    ];

    public function __construct(protected array $config = [])
    {
    }

    public function headers(): array
    {
        if (! $this->compiled) {
            $this->compile();
        }

        return $this->headers;
    }

    protected function compile(): void
    {
        $this->headers = array_merge(
            $this->csp(),
            $this->expectCT(),
            $this->hsts(),
            $this->permissionsPolicy(),
            $this->miscellaneous(),
            $this->clearSiteData(),
        );

        $this->compiled = true;
    }

    protected function csp(): array
    {
        if (isset($this->config['custom-csp'])) {
            if (empty($this->config['custom-csp'])) {
                return [];
            }

            return ['Content-Security-Policy' => $this->config['custom-csp']];
        }

        $config = $this->config['csp'] ?? [];

        if (!($config['enable'] ?? false)) {
            return [];
        }

        $config['script-src']['nonces'] = self::$nonces['script'];

        $config['style-src']['nonces'] = self::$nonces['style'];

        $csp = new CSPBuilder($config);

        return $csp->getHeaderArray(legacy: false);
    }

    /**
     * Strict Transport Security.
     *
     * @return array|string[]
     */
    protected function hsts(): array
    {
        if (! $this->config['hsts']['enable']) {
            return [];
        }

        $hsts = sprintf('max-age=%s; preload;', $this->config['hsts']['max-age']);

        if ($this->config['hsts']['include-sub-domains']) {
            $hsts .= ' includeSubDomains;';
        }

        return ['Strict-Transport-Security' => $hsts];
    }

    protected function expectCT(): array
    {
        $config = $this->config['expect-ct'] ?? [];

        if (!($config['enable'] ?? false)) {
            return [];
        }

        $build[] = $this->maxAge();

        if ($this->config['enforce'] ?? false) {
            $build[] = 'enforce';
        }

        if (!empty($this->config['report-uri'])) {
            $build[] = $this->reportUri();
        }

        return ['Expect-CT' => implode(separator: ', ', array: array_filter($build))];
    }

    /**
     * Generate Clear-Site-Data header.
     *
     * @return array
     */
    protected function clearSiteData(): array
    {
        $config = $this->config['clear-site-data'] ?? [];

        if (!($config['enable'] ?? false)) {
            return [];
        }

        if ($config['all'] ?? false) {
            return ['"*"'];
        }

        $targets = array_intersect_key($config, [
                'cache' => true,
                'cookies' => true,
                'storage' => true,
                'executionContexts' => true,
        ]);

        $needs = array_filter($targets);

        $build = array_map(function (string $directive) {
            return sprintf('"%s"', $directive);
        }, array_keys($needs));

        return ['Clear-Site-Data' => implode(separator: ', ', array: $build)];
    }

    protected function permissionsPolicy(): array
    {
        $config = $this->config['permissions-policy'] ?? [];

        if (!($config['enable'] ?? false)) {
            return [];
        }

        $build = [];

        foreach ($config as $name => $c) {
            if ($name === 'enable') {
                continue;
            }

            if (empty($val = $this->directive($c))) {
                continue;
            }

            $build[] = sprintf('%s=%s', $name, $val);
        }

        return ['Permissions-Policy' => $build];
    }

    /**
     * Get Miscellaneous headers.
     *
     * @return array<string, mixed>
     */
    protected function miscellaneous(): array
    {
        return [
                'X-Content-Type-Options' => $this->config['x-content-type-options'],
                'X-Download-Options' => $this->config['x-download-options'],
                'X-Frame-Options' => $this->config['x-frame-options'],
                'X-Permitted-Cross-Domain-Policies' => $this->config['x-permitted-cross-domain-policies'],
                'X-Powered-By' => $this->config['x-powered-by'] ?? ($this->config['x-power-by'] ??
                                sprintf('CodefyPHP-%s', Application::APP_VERSION)),
                'X-XSS-Protection' => $this->config['x-xss-protection'],
                'Referrer-Policy' => $this->config['referrer-policy'],
                'Server' => $this->config['server'],
                'Cross-Origin-Embedder-Policy' => $this->config['cross-origin-embedder-policy'] ?? '',
                'Cross-Origin-Opener-Policy' => $this->config['cross-origin-opener-policy'] ?? '',
                'Cross-Origin-Resource-Policy' => $this->config['cross-origin-resource-policy'] ?? '',
        ];
    }

    protected function maxAge(): string
    {
        $origin = $this->config['max-age'] ?? 1800;

        // convert to int
        $age = intval(value: $origin);

        // prevent negative value
        $val = max($age, 0);

        return sprintf('max-age=%d', $val);
    }

    /**
     * Get report-uri directive.
     */
    protected function reportUri(): string
    {
        $uri = filter_var(value: $this->config['report-uri'], filter: FILTER_VALIDATE_URL);

        if ($uri === false) {
            return '';
        }

        return sprintf('report-uri="%s"', $uri);
    }

    /**
     * Parse a specific permission policy value.
     *
     * @param array $config
     * @return string
     */
    protected function directive(array $config): string
    {
        if ($config['none'] ?? false) {
            return '()';
        } elseif ($config['*'] ?? false) {
            return '*';
        }

        $origins = $this->origins(origins: $config['origins'] ?? []);

        if ($config['self'] ?? false) {
            array_unshift($origins, 'self');
        }

        return sprintf('(%s)', implode(separator: ' ', array: $origins));
    }

    /**
     * Get valid origins.
     */
    protected function origins(array $origins): array
    {
        // prevent user leave spaces by mistake
        $trimmed = array_map(callback: 'trim', array: $origins);

        // filter array using FILTER_VALIDATE_URL
        $filters = filter_var_array(array: $trimmed, options: FILTER_VALIDATE_URL);

        // get valid value
        $passes = array_filter(array: $filters);

        // ensure indexes are numerically
        $urls = array_values(array: $passes);

        return array_map(callback: function (string $url) {
            return sprintf('"%s"', $url);
        }, array: $urls);
    }

    /**
     * Generate random nonce value for the current request.
     *
     * @throws \Exception
     */
    public static function nonce(string $target = 'script'): string
    {
        $nonce = base64_encode(string: bin2hex(string: random_bytes(length: 8)));

        self::$nonces[$target][] = $nonce;

        return $nonce;
    }

    /**
     * Remove a specific nonce value or flush all nonces for the given target.
     *
     * @param string|null $target
     * @param string|null $nonce
     * @return void
     */
    public static function removeNonce(?string $target = null, ?string $nonce = null): void
    {
        if ($target === null) {
            self::$nonces['script'] = self::$nonces['style'] = [];
        } elseif (isset(self::$nonces[$target])) {
            if ($nonce === null) {
                self::$nonces[$target] = [];
            } elseif (false !== ($idx = array_search(needle: $nonce, haystack: self::$nonces[$target]))) {
                unset(self::$nonces[$target][$idx]);
            }
        }
    }
}
