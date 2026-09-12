<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use InvalidArgumentException;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;

use function usort;

final class ThreatPatternRegistry
{
    private const array HTML_INPUT_SOURCES = [
        'uri',
        'path',
        'query',
        'body',
    ];

    private const array URL_INPUT_SOURCES = [
        'uri',
        'query',
        'body',
    ];

    private const array FILE_PATH_INPUT_SOURCES = [
        'uri',
        'path',
        'query',
        'body',
    ];

    private const array REQUEST_PATH_SOURCES = [
        'path',
    ];

    private const array SQL_INPUT_SOURCES = [
        'query',
        'body',
    ];

    private const array COMMAND_INPUT_SOURCES = [
        'query',
        'body',
    ];

    /**
     * @var list<ThreatPattern>|null
     */
    private ?array $patterns = null;

    public function __construct(protected ConfigContainer $config)
    {
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    public function all(): array
    {

        if ($this->patterns !== null) {
            return $this->patterns;
        }

        $patterns = [
            ...$this->whenEnabled('sql_injection', fn (): array => $this->sqlInjection()),
            ...$this->whenEnabled('xss', fn (): array => $this->xss()),
            ...$this->whenEnabled('rce', fn (): array => $this->rce()),
            ...$this->whenEnabled('file_traversal', fn (): array => $this->fileTraversal()),
            ...$this->whenEnabled('ssrf', fn (): array => $this->ssrf()),
            ...$this->whenEnabled('scanner_path_probe', fn (): array => $this->scannerProbes()),
            ...$this->whenEnabled('sensitive_file_probe', fn (): array => $this->sensitiveFiles()),
            ...$this->whenEnabled('wordpress_probe', fn (): array => $this->wordpressProbes()),
            ...$this->whenEnabled('php_probe', fn (): array => $this->phpProbes()),
        ];

        usort(
            $patterns,
            static function (
                ThreatPattern $left,
                ThreatPattern $right
            ): int {
                $priorityComparison = $right->priority <=> $left->priority;

                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }

                $groupComparison = $left->group <=> $right->group;

                if ($groupComparison !== 0) {
                    return $groupComparison;
                }

                return $left->regex <=> $right->regex;
            }
        );

        return $this->patterns = $patterns;
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function sqlInjection(): array
    {
        $patterns = [
            '/\bunion\s+select\b/i',
            '/\bselect\s+.+\bfrom\b/i',
            '/\binsert\s+into\b/i',
            '/\bupdate\s+.+\bset\b/i',
            '/\bdelete\s+from\b/i',
            '/\bdrop\s+table\b/i',
            '/\balter\s+table\b/i',
            '/\btruncate\s+table\b/i',
            '/\bor\s+1\s*=\s*1\b/i',
            '/\band\s+1\s*=\s*1\b/i',
            '/\bor\s+[\'"]?a[\'"]?\s*=\s*[\'"]?a[\'"]?/i',
            '/--\s*$/',
            '/\/\*.*\*\//s',
            '/\bbenchmark\s*\(/i',
            '/\bsleep\s*\(/i',
            '/\bload_file\s*\(/i',
            '/\boutfile\b/i',
            '/\binformation_schema\b/i',
            '/\bconcat\s*\(/i',
            '/\bgroup_concat\s*\(/i',
        ];

        $allowedSources = $this->allowedSources(
            group: 'sql_injection',
            defaults: self::SQL_INPUT_SOURCES
        );

        return $this->map(
            patterns: $this->configurePatterns(
                group: 'sql_injection',
                builtInPatterns: $patterns
            ),
            group: 'sql_injection',
            type: 'sql_injection',
            severity: 'critical',
            confidence: 95.0,
            allowedSources: $allowedSources,
            priority: 90
        );
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function xss(): array
    {
        $patterns = [
            '/<script\b[^>]*>/i',
            '/<\/script>/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:\s*text\/html/i',
            '/onerror\s*=/i',
            '/onload\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/onfocus\s*=/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<svg\b[^>]*on\w+\s*=/i',
            '/document\.cookie/i',
            '/document\.location/i',
            '/window\.location/i',
            '/alert\s*\(/i',
            '/confirm\s*\(/i',
            '/prompt\s*\(/i',
        ];

        $allowedSources = $this->allowedSources(
            group: 'xss',
            defaults: self::HTML_INPUT_SOURCES
        );

        return $this->map(
            patterns: $this->configurePatterns(
                group: 'xss',
                builtInPatterns: $patterns
            ),
            group: 'xss',
            type: 'xss',
            severity: 'high',
            confidence: 90.0,
            allowedSources: $allowedSources,
            priority: 60
        );
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function rce(): array
    {
        $patterns = [
            '/\bsystem\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bproc_open\s*\(/i',
            '/\bpopen\s*\(/i',
            '/\beval\s*\(/i',
            '/\bassert\s*\(/i',
            '/base64_decode\s*\(/i',
            '/php:\/\/input/i',
            '/php:\/\/filter/i',
            '/expect:\/\//i',
            '/\bcurl\s+/i',
            '/\bwget\s+/i',
            '/\bchmod\s+/i',
            '/\bchown\s+/i',
            '/\brm\s+-rf\b/i',
            '/\bnc\s+-e\b/i',
            '/\bbash\s+-i\b/i',
            '/\/bin\/sh/i',
        ];

        $allowedSources = $this->allowedSources(
            group: 'rce',
            defaults: self::COMMAND_INPUT_SOURCES
        );

        return $this->map(
            patterns: $this->configurePatterns(
                group: 'rce',
                builtInPatterns: $patterns
            ),
            group: 'rce',
            type: 'remote_code_execution',
            severity: 'critical',
            confidence: 98.0,
            allowedSources: $allowedSources,
            priority: 100
        );
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function fileTraversal(): array
    {
        $patterns = [
            '#(?:^|[\\\\/])\.\.(?:[\\\\/]|$)#',
            '#%2e%2e(?:%2f|%5c)#i',
            '#\.\.%2f#i',
            '#\.\.%5c#i',
            '#%252e%252e(?:%252f|%255c)#i',
        ];

        $allowedSources = $this->allowedSources(
            group: 'file_traversal',
            defaults: self::FILE_PATH_INPUT_SOURCES
        );

        return $this->map(
            patterns: $this->configurePatterns(
                group: 'file_traversal',
                builtInPatterns: $patterns
            ),
            group: 'file_traversal',
            type: 'file_traversal',
            severity: 'high',
            confidence: 92.0,
            allowedSources: $allowedSources,
            priority: 80
        );
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function ssrf(): array
    {
        $patterns = [
            '/(?<![\w.-])169\.254\.169\.254(?::\d+)?(?:[\/?#]|$)/i',
            '/(?<![\w.-])metadata\.google\.internal(?::\d+)?(?:[\/?#]|$)/i',
            '/(?<![\w.-])metadata\.azure\.com(?::\d+)?(?:[\/?#]|$)/i',
            '/(?<![\w.-])localhost(?::\d+)?(?:[\/?#]|$)/i',

            '/(?<![\w.-])127(?:\.\d{1,3}){3}(?::\d+)?(?:[\/?#]|$)/i',
            '/(?<![\w.-])0\.0\.0\.0(?::\d+)?(?:[\/?#]|$)/i',

            '/(?:^|[\/\[\s])::1(?::\d+)?(?:$|[\/\]?#\s])/i',

            '/\bfile:\/\//i',
            '/\bgopher:\/\//i',
            '/\bdict:\/\//i',
            '/\bftp:\/\//i',

            '/\bhttps?:\/\/10(?:\.\d{1,3}){3}(?::\d+)?(?:[\/?#]|$)/i',
            '/\bhttps?:\/\/172\.(?:1[6-9]|2\d|3[01])(?:\.\d{1,3}){2}(?::\d+)?(?:[\/?#]|$)/i',
            '/\bhttps?:\/\/192\.168(?:\.\d{1,3}){2}(?::\d+)?(?:[\/?#]|$)/i',
        ];

        $allowedSources = $this->allowedSources(
            group: 'ssrf',
            defaults: self::URL_INPUT_SOURCES
        );

        return $this->map(
            patterns: $this->configurePatterns(
                group: 'ssrf',
                builtInPatterns: $patterns
            ),
            group: 'ssrf',
            type: 'ssrf',
            severity: 'high',
            confidence: 88.0,
            allowedSources: $allowedSources,
            priority: 70
        );
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function scannerProbes(): array
    {
        $paths = [
            'cpanel', 'phpmyadmin', 'pma',
            'mysql', 'dbadmin', 'webadmin', 'server-status', 'server-info',
            'actuator', 'actuator/env', 'actuator/health', 'debug/default/view',
            'vendor/phpunit', 'phpunit', 'cgi-bin', 'boaform', 'HNAP1',
            'shell', 'cmd', 'console', 'manager/html', 'solr/admin',
            'graphql', 'api/docs', 'swagger', 'swagger-ui', 'openapi.json',
            'elmah.axd', 'trace.axd', 'remote/login', 'vpn/index.html',
            'owa/auth/logon.aspx', 'ecp', 'autodiscover/autodiscover.xml',
        ];

        $paths = $this->configureValues(
            group: 'scanner_path_probe',
            builtInValues: $paths
        );

        $allowedSources = $this->allowedSources(
            group: 'scanner_path_probe',
            defaults: self::REQUEST_PATH_SOURCES
        );

        return array_map(
            fn (string $path): ThreatPattern => new ThreatPattern(
                group: 'scanner_path_probe',
                type: 'bot_scanner',
                severity: 'medium',
                confidence: 75.0,
                regex: '#/(?:' . preg_quote($path, '#') . ')(?:/|$|\?)#i',
                allowedSources: $allowedSources,
                priority: 40
            ),
            $paths
        );
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function sensitiveFiles(): array
    {
        $files = [
            '.env', '.git/config', '.git/HEAD', '.svn/entries', '.DS_Store',
            'composer.json', 'composer.lock', 'package.json', 'yarn.lock',
            'config.php', 'configuration.php', 'settings.php', 'database.php',
            'backup.sql', 'dump.sql', 'db.sql', 'database.sql', 'site.sql',
            'backup.zip', 'backup.tar.gz', 'www.zip', 'public.zip',
            'credentials.json', 'service-account.json', 'id_rsa', 'id_dsa',
            'web.config', 'nginx.conf', 'apache.conf', 'httpd.conf',
            '.enc.key', '.env.enc',
        ];

        $files = $this->configureValues(
            group: 'sensitive_file_probe',
            builtInValues: $files
        );

        return array_map(
            fn (string $file): ThreatPattern => new ThreatPattern(
                group: 'sensitive_file_probe',
                type: 'sensitive_file_probe',
                severity: 'high',
                confidence: 90.0,
                regex: '#/(?:' . preg_quote($file, '#') . ')(?:$|\?)#i',
                allowedSources: $this->allowedSources(
                    group: 'sensitive_file_probe',
                    defaults: self::REQUEST_PATH_SOURCES
                ),
                priority: 50
            ),
            $files
        );
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function wordpressProbes(): array
    {
        $paths = [
            'wp-login.php', 'wp-admin', 'xmlrpc.php', 'wp-config.php',
            'wp-content/debug.log', 'wp-content/uploads', 'wp-json',
            'wp-includes', 'wp-content/plugins', 'wp-content/themes',
            'license.txt', 'readme.html', 'wp-cron.php', 'wp-load.php',
            'wp-blog-header.php', 'wp-comments-post.php',
        ];

        $paths = $this->configureValues(
            group: 'wordpress_probe',
            builtInValues: $paths,
            includeLegacy: false
        );

        $allowedSources = $this->allowedSources(
            group: 'wordpress_probe',
            defaults: self::REQUEST_PATH_SOURCES
        );

        return array_map(
            fn (string $path): ThreatPattern => new ThreatPattern(
                group: 'wordpress_probe',
                type: 'cms_probe',
                severity: 'medium',
                confidence: 80.0,
                regex: '#/(?:' . preg_quote($path, '#') . ')(?:/|$|\?)#i',
                allowedSources: $allowedSources,
                priority: 30
            ),
            $paths
        );
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function phpProbes(): array
    {
        $files = [
            'info.php', 'phpinfo.php', 'test.php', 'debug.php', 'shell.php',
            'cmd.php', 'upload.php', 'uploader.php', 'filemanager.php',
            'adminer.php', 'adminer-4.8.1.php', 'db.php', 'sql.php',
            'config.inc.php', 'connect.php', 'database.php', 'env.php',
            'vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php',
        ];

        $files = $this->configureValues(
            group: 'php_probe',
            builtInValues: $files
        );

        return array_map(
            fn (string $file): ThreatPattern => new ThreatPattern(
                group: 'php_probe',
                type: 'php_probe',
                severity: 'high',
                confidence: 88.0,
                regex: '#/(?:' . preg_quote($file, '#') . ')(?:$|\?)#i',
                allowedSources: $this->allowedSources(
                    group: 'php_probe',
                    defaults: self::REQUEST_PATH_SOURCES
                ),
                priority: 31
            ),
            $files
        );
    }

    /**
     * @param list<string> $patterns
     * @param list<string> $allowedSources
     * @return list<ThreatPattern>
     */
    private function map(
        array $patterns,
        string $group,
        string $type,
        string $severity,
        float $confidence,
        array $allowedSources = [],
        int $priority = 0
    ): array {
        return array_map(
            fn (string $regex): ThreatPattern => new ThreatPattern(
                group: $group,
                type: $type,
                severity: $severity,
                confidence: $confidence,
                regex: $this->validateRegex($regex, $group),
                allowedSources: $allowedSources,
                priority: $priority
            ),
            $patterns
        );
    }

    /**
     * @param callable(): list<ThreatPattern> $patterns
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    private function whenEnabled(string $group, callable $patterns): array
    {
        if (
                ! $this->config->boolean(
                    key: 'firewall.rules.' . $group . '.enabled',
                    default: true
                )
        ) {
            return [];
        }

        return $patterns();
    }

    /**
     * @param list<string> $builtInPatterns
     * @return list<string>
     * @throws TypeException
     */
    private function configurePatterns(
        string $group,
        array $builtInPatterns
    ): array {
        $replace = $this->config->array(
            key: 'firewall.rules.' . $group . '.replace',
            default: []
        );

        $patterns = $replace !== []
        ? $replace
        : $builtInPatterns;

        $remove = $this->config->array(
            key: 'firewall.rules.' . $group . '.remove',
            default: []
        );

        if ($remove !== []) {
            $patterns = array_values(
                array_filter(
                    $patterns,
                    static fn (string $pattern): bool =>
                        ! in_array(
                            needle: $pattern,
                            haystack: $remove,
                            strict: true
                        )
                )
            );
        }

        return [
            ...$patterns,
            /*
             * Deprecated legacy additions.
             *
             * Remove in the next major version (4.0).
             */
            ...$this->config->array(
                key: 'firewall.' . $group,
                default: []
            ),

            ...$this->config->array(
                key: 'firewall.rules.' . $group . '.add',
                default: []
            ),
        ];
    }

    /**
     * @param list<string> $builtInValues
     * @return list<string>
     * @throws TypeException
     */
    private function configureValues(
        string $group,
        array $builtInValues,
        bool $includeLegacy = true
    ): array {
        $legacyValues = $includeLegacy
        ? $this->config->array(
            key: 'firewall.' . $group,
            default: []
        )
        : [];

        $replace = $this->config->array(
            key: 'firewall.rules.' . $group . '.replace',
            default: []
        );

        $values = [
            ...($replace !== [] ? $replace : $builtInValues),

            /*
             * Deprecated legacy additions.
             */
            ...$legacyValues,

            ...$this->config->array(
                key: 'firewall.rules.' . $group . '.add',
                default: []
            ),
        ];

        $remove = $this->config->array(
            key: 'firewall.rules.' . $group . '.remove',
            default: []
        );

        if ($remove === []) {
            return array_values(array_unique($values));
        }

        return array_values(
            array_unique(
                array_filter(
                    $values,
                    static fn (string $value): bool =>
                        ! in_array(
                            needle: $value,
                            haystack: $remove,
                            strict: true
                        )
                )
            )
        );
    }

    /**
     * @param list<string> $defaults
     * @return list<string>
     * @throws TypeException
     */
    private function allowedSources(
        string $group,
        array $defaults
    ): array {
        $configuredSources = $this->config->array(
            key: 'firewall.rules.' . $group . '.sources',
            default: []
        );

        if ($configuredSources === []) {
            return $defaults;
        }

        $supportedSources = [
            'method',
            'uri',
            'path',
            'query',
            'body',
            'header',
            'cookie',
        ];

        $sources = array_values(
            array_filter(
                $configuredSources,
                static fn (mixed $source): bool =>
                    is_string($source)
                    && in_array(
                        $source,
                        $supportedSources,
                        true
                    )
            )
        );

        return $sources !== [] ? $sources : $defaults;
    }

    private function validateRegex(string $regex, string $group): string
    {
        if (@preg_match($regex, '') === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid firewall regex configured for group [%s]: %s. Error: %s',
                    $group,
                    $regex,
                    preg_last_error_msg()
                )
            );
        }

        return $regex;
    }
}
