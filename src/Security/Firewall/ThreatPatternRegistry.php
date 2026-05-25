<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;

use function array_merge;

final class ThreatPatternRegistry
{
    public function __construct(protected ConfigContainer $config)
    {
    }

    /**
     * @return list<ThreatPattern>
     * @throws TypeException
     */
    public function all(): array
    {
        return [
            ...$this->sqlInjection(),
            ...$this->xss(),
            ...$this->rce(),
            ...$this->fileTraversal(),
            ...$this->ssrf(),
            ...$this->scannerProbes(),
            ...$this->sensitiveFiles(),
            ...$this->wordpressProbes(),
            ...$this->phpProbes(),
        ];
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

        return $this->map(
            array_merge(
                $patterns,
                $this->config->array(key: 'firewall.sql_injection')
            ),
            'sql_injection',
            'critical',
            95.0
        );
    }

    /**
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

        return $this->map(
            array_merge(
                $patterns,
                $this->config->array(key: 'firewall.xss')
            ),
            'xss',
            'high',
            90.0
        );
    }

    /**
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

        return $this->map(
            array_merge(
                $patterns,
                $this->config->array('firewall.rce')
            ),
            'remote_code_execution',
            'critical',
            98.0
        );
    }

    /**
     * @throws TypeException
     */
    private function fileTraversal(): array
    {
        $patterns = [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/%2e%2e%2f/i',
            '/%2e%2e%5c/i',
            '/etc\/passwd/i',
            '/etc\/shadow/i',
            '/boot\.ini/i',
            '/win\.ini/i',
            '/windows\/system32/i',
            '/\/proc\/self\/environ/i',
            '/\/proc\/version/i',
            '/\/var\/log\//i',
            '/\/var\/www\//i',
            '/\/home\/[^\/]+\/\.ssh/i',
            '/id_rsa/i',
        ];

        return $this->map(
            array_merge(
                $patterns,
                $this->config->array(key: 'firewall.file_traversal')
            ),
            'file_traversal',
            'high',
            92.0
        );
    }

    /**
     * @throws TypeException
     */
    private function ssrf(): array
    {
        $patterns = [
            '/169\.254\.169\.254/i',
            '/metadata\.google\.internal/i',
            '/metadata\.azure\.com/i',
            '/localhost/i',
            '/127\.0\.0\.1/',
            '/0\.0\.0\.0/',
            '/::1/',
            '/file:\/\//i',
            '/gopher:\/\//i',
            '/dict:\/\//i',
            '/ftp:\/\//i',
            '/http:\/\/10\./i',
            '/http:\/\/172\.(1[6-9]|2[0-9]|3[0-1])\./i',
            '/http:\/\/192\.168\./i',
        ];

        return $this->map(
            array_merge(
                $patterns,
                $this->config->array(key: 'firewall.ssrf')
            ),
            'ssrf',
            'high',
            88.0
        );
    }

    /**
     * @throws TypeException
     */
    private function scannerProbes(): array
    {
        $paths = [
            'admin', 'administrator', 'login', 'cpanel', 'phpmyadmin', 'pma',
            'mysql', 'dbadmin', 'webadmin', 'server-status', 'server-info',
            'actuator', 'actuator/env', 'actuator/health', 'debug/default/view',
            'vendor/phpunit', 'phpunit', 'cgi-bin', 'boaform', 'HNAP1',
            'shell', 'cmd', 'console', 'manager/html', 'solr/admin',
            'graphql', 'api/docs', 'swagger', 'swagger-ui', 'openapi.json',
            'elmah.axd', 'trace.axd', 'remote/login', 'vpn/index.html',
            'owa/auth/logon.aspx', 'ecp', 'autodiscover/autodiscover.xml',
        ];

        $paths = array_merge($paths, $this->config->array(key: 'firewall.scanner_path_probe'));

        return array_map(
            fn (string $path): ThreatPattern => new ThreatPattern(
                'bot_scanner',
                'medium',
                75.0,
                '#/(?:' . preg_quote($path, '#') . ')(?:/|$|\?)#i'
            ),
            $paths
        );
    }

    /**
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
        ];

        $files = array_merge($files, $this->config->array(key: 'firewall.sensitive_file_probe'));

        return array_map(
            fn (string $file): ThreatPattern => new ThreatPattern(
                'sensitive_file_probe',
                'high',
                90.0,
                '#/(?:' . preg_quote($file, '#') . ')(?:$|\?)#i'
            ),
            $files
        );
    }

    private function wordpressProbes(): array
    {
        $paths = [
            'wp-login.php', 'wp-admin', 'xmlrpc.php', 'wp-config.php',
            'wp-content/debug.log', 'wp-content/uploads', 'wp-json',
            'wp-includes', 'wp-content/plugins', 'wp-content/themes',
            'license.txt', 'readme.html', 'wp-cron.php', 'wp-load.php',
            'wp-blog-header.php', 'wp-comments-post.php',
        ];

        return array_map(
            fn (string $path): ThreatPattern => new ThreatPattern(
                'cms_probe',
                'medium',
                80.0,
                '#/(?:' . preg_quote($path, '#') . ')(?:/|$|\?)#i'
            ),
            $paths
        );
    }

    /**
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

        $files = array_merge($files, $this->config->array(key: 'firewall.php_probe'));

        return array_map(
            fn (string $file): ThreatPattern => new ThreatPattern(
                'php_probe',
                'high',
                88.0,
                '#/(?:' . preg_quote($file, '#') . ')(?:$|\?)#i'
            ),
            $files
        );
    }

    /**
     * @param list<string> $patterns
     * @return list<ThreatPattern>
     */
    private function map(array $patterns, string $type, string $severity, float $confidence): array
    {
        return array_map(
            fn (string $regex): ThreatPattern => new ThreatPattern($type, $severity, $confidence, $regex),
            $patterns
        );
    }
}
