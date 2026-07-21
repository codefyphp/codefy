<?php

declare(strict_types=1);

use Codefy\Framework\Security\Firewall\FirewallExclusionPolicy;
use Codefy\Framework\Security\Firewall\NullThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatDetector;
use Codefy\Framework\Security\Firewall\ThreatMatch;
use Codefy\Framework\Security\Firewall\ThreatPattern;
use Codefy\Framework\Security\Firewall\ThreatPatternRegistry;

beforeEach(function (): void {
    $this->detector = new ThreatDetector(
        new ThreatPatternRegistry(codefy()->make('codefy.config')),
        new FirewallExclusionPolicy(codefy()->make('codefy.config')),
        new NullThreatLogger()
    );
});

it('does not detect a Chrome mobile version as SSRF', function (): void {
    $request = firewall_request()
        ->withHeader(
            'User-Agent',
            'Mozilla/5.0 (Linux; Android 10; K) '
            . 'AppleWebKit/537.36 (KHTML, like Gecko) '
            . 'Chrome/150.0.0.0 Mobile Safari/537.36'
        );

    expect($this->detector->detect($request))->toBeNull();
});


it('does not inspect User-Agent headers using SSRF rules', function (
    string $userAgent
): void {
    $request = firewall_request()
        ->withHeader('User-Agent', $userAgent);

    expect($this->detector->detect($request))->toBeNull();
})->with([
    'Chrome mobile' => [
        'Mozilla/5.0 Chrome/150.0.0.0 Mobile Safari/537.36',
    ],
    'Chrome desktop' => [
        'Mozilla/5.0 Chrome/127.0.0.0 Safari/537.36',
    ],
    'Chromium' => [
        'Mozilla/5.0 Chromium/126.0.0.0 Safari/537.36',
    ],
    'Edge' => [
        'Mozilla/5.0 Chrome/126.0.0.0 Edg/126.0.0.0',
    ],
]);

it('detects metadata service SSRF in a body field', function (): void {
    $request = firewall_request('POST', '/fetch')
        ->withParsedBody([
            'callback_url' => 'http://169.254.169.254/latest/meta-data',
        ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->toBeInstanceOf(ThreatMatch::class)
        ->and($match->group)->toBe('ssrf')
        ->and($match->type)->toBe('ssrf')
        ->and($match->source)->toBe('body')
        ->and($match->field)->toBe('callback_url')
        ->and($match->excluded)->toBeFalse();
});

it('detects loopback SSRF in a body field', function (
    string $destination
): void {
    $request = firewall_request('POST', '/fetch')
        ->withParsedBody([
            'url' => $destination,
        ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('ssrf')
        ->and($match->source)->toBe('body')
        ->and($match->field)->toBe('url');
})->with([
    'localhost' => ['http://localhost/admin'],
    'IPv4 loopback' => ['http://127.0.0.1/admin'],
    'unspecified IPv4' => ['http://0.0.0.0/admin'],
    'IPv6 loopback' => ['http://[::1]/admin'],
]);

it('detects private network SSRF destinations', function (
    string $destination
): void {
    $request = firewall_request('POST', '/proxy')
        ->withParsedBody([
            'target' => $destination,
        ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('ssrf')
        ->and($match->field)->toBe('target');
})->with([
    '10/8 network' => ['http://10.0.0.10/private'],
    '172.16/12 lower boundary' => ['http://172.16.0.1/private'],
    '172.16/12 upper boundary' => ['http://172.31.255.254/private'],
    '192.168/16 network' => ['http://192.168.1.20/private'],
]);

it('detects dangerous URL schemes in body fields', function (
    string $destination
): void {
    $request = firewall_request('POST', '/fetch')
            ->withParsedBody([
                    'resource' => $destination,
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('ssrf')
            ->and($match->field)->toBe('resource');
})->with([
        'file scheme' => [
                'file:///tmp/firewall-test-resource.txt',
        ],
        'gopher scheme' => [
                'gopher://example.com:6379/_INFO',
        ],
        'dict scheme' => [
                'dict://example.com:11211/stats',
        ],
        'FTP scheme' => [
                'ftp://example.com/private',
        ],
]);

it('detects dangerous system file URLs as SSRF', function (
    string $destination
): void {
    $request = firewall_request('POST', '/fetch')
            ->withParsedBody([
                    'resource' => $destination,
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('ssrf')
            ->and($match->type)->toBe('ssrf')
            ->and($match->field)->toBe('resource');
})->with([
        'passwd file URL' => [
                'file:///etc/passwd',
        ],
        'shadow file URL' => [
                'file:///etc/shadow',
        ],
]);

it('prioritizes file traversal over SSRF when a file URL contains traversal', function (
    string $destination
): void {
    $request = firewall_request('POST', '/fetch')
            ->withParsedBody([
                    'resource' => $destination,
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('file_traversal')
            ->and($match->type)->toBe('file_traversal')
            ->and($match->field)->toBe('resource');
})->with([
        'passwd traversal URL' => [
                'file:///var/www/../../etc/passwd',
        ],
        'shadow traversal URL' => [
                'file:///var/www/../../etc/shadow',
        ],
        'encoded passwd traversal URL' => [
                'file:///var/www/%2e%2e%2f%2e%2e%2fetc%2fpasswd',
        ],
]);

it('detects private-key file URLs as SSRF', function (): void {
    $request = firewall_request('POST', '/fetch')
            ->withParsedBody([
                    'resource' => 'file:///home/joshua/.ssh/id_rsa',
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('ssrf')
            ->and($match->type)->toBe('ssrf')
            ->and($match->field)->toBe('resource');
});

it('detects SSRF in query parameters', function (): void {
    $request = firewall_request(
        'GET',
        '/proxy?url=http%3A%2F%2F127.0.0.1%2Fadmin'
    )->withQueryParams([
        'url' => 'http://127.0.0.1/admin',
    ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('ssrf')
        ->and($match->source)->toBe('query')
        ->and($match->field)->toBe('url');
});

it('records nested body field names using dot notation', function (): void {
    $request = firewall_request('POST', '/webhooks')
        ->withParsedBody([
            'webhook' => [
                'settings' => [
                    'callback_url' => 'http://127.0.0.1/internal',
                ],
            ],
        ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->source)->toBe('body')
        ->and($match->field)
        ->toBe('webhook.settings.callback_url');
});

it('does not match unrelated version numbers as SSRF', function (
    string $value
): void {
    $request = firewall_request('POST', '/save')
        ->withParsedBody([
            'description' => $value,
        ]);

    expect($this->detector->detect($request))->toBeNull();
})->with([
    'semantic version' => ['Version 150.0.0.0 was released'],
    'browser version' => ['Chrome/150.0.0.0'],
    'package version' => ['package-name 0.0.0.0-beta'],
]);

it('detects the unspecified IPv4 address in host contexts', function (
    string $value
): void {
    $request = firewall_request('POST', '/save')
            ->withParsedBody([
                    'resource' => $value,
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('ssrf');
})->with([
        'plain host' => ['0.0.0.0'],
        'host with port' => ['0.0.0.0:8080'],
        'HTTP URL' => ['http://0.0.0.0/admin'],
        'HTTPS URL' => ['https://0.0.0.0/private'],
        'host with path' => ['0.0.0.0/internal'],
]);

it('does not treat dotted version values as SSRF hosts', function (
    string $value
): void {
    $request = firewall_request('POST', '/save')
            ->withParsedBody([
                    'description' => $value,
            ]);

    expect($this->detector->detect($request))->toBeNull();
})->with([
        'prerelease version' => ['package-name 0.0.0.0-beta'],
        'build version' => ['build-0.0.0.0-dev'],
        'prefixed value' => ['foo0.0.0.0'],
        'extended address' => ['0.0.0.0.1'],
]);

it('does not apply scanner probe rules to body content', function (): void {
    $request = firewall_request('POST', '/articles')
        ->withParsedBody([
            'title' => 'How to secure phpMyAdmin',
            'content' => 'This guide discusses /phpmyadmin/ security.',
        ]);

    expect($this->detector->detect($request))->toBeNull();
});

it('detects scanner probes in request paths', function (
    string $path
): void {
    $request = firewall_request('GET', $path);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('scanner_path_probe')
        ->and($match->type)->toBe('bot_scanner')
        ->and($match->source)->toBeIn(['uri', 'path']);
})->with([
    'phpMyAdmin' => ['/phpmyadmin/'],
    'PMA alias' => ['/pma/'],
    'server status' => ['/server-status'],
    'Swagger' => ['/swagger-ui/'],
    'PHPUnit probe' => ['/vendor/phpunit/'],
]);

it('detects sensitive file probes in request paths', function (
    string $path
): void {
    $request = firewall_request('GET', $path);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('sensitive_file_probe')
        ->and($match->type)->toBe('sensitive_file_probe');
})->with([
    'environment file' => ['/.env'],
    'Git config' => ['/.git/config'],
    'Composer lock' => ['/composer.lock'],
    'database dump' => ['/backup.sql'],
    'private key' => ['/id_rsa'],
]);

it('detects WordPress probes in request paths', function (
    string $path
): void {
    $request = firewall_request('GET', $path);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('wordpress_probe')
        ->and($match->type)->toBe('cms_probe');
})->with([
    'login' => ['/wp-login.php'],
    'admin' => ['/wp-admin/'],
    'XML-RPC' => ['/xmlrpc.php'],
    'WordPress API' => ['/wp-json/'],
]);

it('detects PHP probes in request paths', function (
    string $path
): void {
    $request = firewall_request('GET', $path);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('php_probe')
        ->and($match->type)->toBe('php_probe');
})->with([
    'phpinfo' => ['/phpinfo.php'],
    'shell' => ['/shell.php'],
    'Adminer' => ['/adminer.php'],
    'SQL utility' => ['/sql.php'],
]);

it('detects SQL injection in body fields', function (
    string $payload
): void {
    $request = firewall_request('POST', '/search')
        ->withParsedBody([
            'search' => $payload,
        ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('sql_injection')
        ->and($match->source)->toBe('body')
        ->and($match->field)->toBe('search');
})->with([
    'union select' => ["' UNION SELECT password FROM cms_user"],
    'tautology' => ["' OR 1=1"],
    'drop table' => ['DROP TABLE cms_user'],
    'sleep' => ['SLEEP(10)'],
    'information schema' => [
        'SELECT * FROM information_schema.tables',
    ],
]);

it('detects SQL injection in query parameters', function (): void {
    $request = firewall_request('GET', '/users')
        ->withQueryParams([
            'id' => '1 OR 1=1',
        ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('sql_injection')
        ->and($match->source)->toBe('query')
        ->and($match->field)->toBe('id');
});

it('does not apply SQL injection rules to User-Agent', function (): void {
    $request = firewall_request()
        ->withHeader(
            'User-Agent',
            'ExampleBot SELECT value FROM version'
        );

    expect($this->detector->detect($request))->toBeNull();
});

it('detects XSS in a body field', function (
    string $payload
): void {
    $request = firewall_request('POST', '/comments')
        ->withParsedBody([
            'comment' => $payload,
        ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('xss')
        ->and($match->source)->toBe('body')
        ->and($match->field)->toBe('comment');
})->with([
    'script element' => ['<script>alert(1)</script>'],
    'event handler' => ['<img src="x" onerror="alert(1)">'],
    'JavaScript scheme' => ['javascript:alert(1)'],
    'iframe' => ['<iframe src="https://example.com"></iframe>'],
    'document cookie' => ['document.cookie'],
]);

it('detects remote code execution payloads', function (
    string $payload
): void {
    $request = firewall_request('POST', '/execute')
        ->withParsedBody([
            'value' => $payload,
        ]);

    $match = $this->detector->detect($request);

    expect($match)
        ->not->toBeNull()
        ->and($match->group)->toBe('rce')
        ->and($match->type)->toBe('remote_code_execution');
})->with([
    'system' => ['system("id")'],
    'shell exec' => ['shell_exec("whoami")'],
    'PHP input' => ['php://input'],
    'base64 decode' => ['base64_decode($payload)'],
    'shell invocation' => ['/bin/sh -c id'],
]);

it('detects file traversal in query parameters', function (
    string $payload
): void {
    $request = firewall_request('GET', '/download')
            ->withQueryParams([
                    'file' => $payload,
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('file_traversal')
            ->and($match->field)->toBe('file');
})->with([
        'Unix traversal' => [
                '../../etc/passwd',
        ],
        'Windows traversal' => [
                '..\\..\\windows\\win.ini',
        ],
        'encoded Unix traversal' => [
                '%2e%2e%2fetc%2fpasswd',
        ],
        'encoded Windows traversal' => [
                '%2e%2e%5cwindows%5cwin.ini',
        ],
        'SSH key traversal' => [
                '../../home/user/.ssh/id_rsa',
        ],
]);

it('ignores empty input values', function (): void {
    $request = firewall_request('POST', '/save')
        ->withQueryParams([
            'search' => '',
        ])
        ->withParsedBody([
            'title' => '',
            'description' => null,
        ]);

    expect($this->detector->detect($request))->toBeNull();
});

it('handles scalar body values', function (): void {
    $request = firewall_request('POST', '/save')
        ->withParsedBody([
            'enabled' => true,
            'count' => 10,
            'ratio' => 1.5,
        ]);

    expect($this->detector->detect($request))->toBeNull();
});

it('does not mutate the original request', function (): void {
    $original = codefy()->request;

    $modified = $original
        ->withMethod('POST')
        ->withParsedBody([
            'url' => 'http://127.0.0.1/private',
        ]);

    expect($original)
        ->not->toBe($modified)
        ->and($original->getMethod())
        ->not->toBe($modified->getMethod());
});

it('does not classify absolute paths as traversal without traversal sequences', function (
    string $path
): void {
    $request = firewall_request('GET', '/download')
        ->withQueryParams([
            'file' => $path,
        ]);

    expect($this->detector->detect($request))->toBeNull();
})->with([
    'Unix home path' => [
        '/home/user/documents/report.pdf',
    ],
    'Unix SSH key path' => [
        '/home/user/.ssh/id_rsa',
    ],
    'Windows file path' => [
        'C:\\Users\\user\\documents\\report.pdf',
    ],
]);

it('supports legacy regex rule additions', function (): void {
    $config = firewall_config([
        'sql_injection' => [
            '/legacy-sql-payload/i',
        ],
    ]);

    $registry = new ThreatPatternRegistry($config);

    expect(
        array_any(
            $registry->all(),
            static fn (ThreatPattern $pattern): bool =>
                $pattern->group === 'sql_injection'
                && $pattern->regex === '/legacy-sql-payload/i'
        )
    )->toBeTrue();
});

it('supports new regex rule additions', function (): void {
    $config = firewall_config([
        'rules' => [
            'sql_injection' => [
                'add' => [
                    '/new-sql-payload/i',
                ],
            ],
        ],
    ]);

    $registry = new ThreatPatternRegistry($config);

    expect(
        array_any(
            $registry->all(),
            static fn (ThreatPattern $pattern): bool =>
                $pattern->group === 'sql_injection'
                && $pattern->regex === '/new-sql-payload/i'
        )
    )->toBeTrue();
});

it('supports legacy sensitive file additions', function (): void {
    $config = firewall_config([
        'sensitive_file_probe' => [
            'custom-secret.json',
        ],
    ]);

    $registry = new ThreatPatternRegistry($config);

    expect(
        array_any(
            $registry->all(),
            static fn (ThreatPattern $pattern): bool =>
                $pattern->group === 'sensitive_file_probe'
                && str_contains(
                    haystack: $pattern->regex,
                    needle: 'custom\\-secret\\.json'
                )
        )
    )->toBeTrue();
});

it('supports new sensitive file additions', function (): void {
    $config = firewall_config([
        'rules' => [
            'sensitive_file_probe' => [
                'add' => [
                    'custom-secret.json',
                ],
            ],
        ],
    ]);

    expect(
        $config->array(
            key: 'firewall.rules.sensitive_file_probe.add',
            default: []
        )
    )->toBe([
        'custom-secret.json',
    ]);

    $registry = new ThreatPatternRegistry($config);

    expect(
        array_any(
            $registry->all(),
            static fn (ThreatPattern $pattern): bool =>
                $pattern->group === 'sensitive_file_probe'
                && str_contains(
                    haystack: $pattern->regex,
                    needle: 'custom\\-secret\\.json'
                )
        )
    )->toBeTrue();
});
