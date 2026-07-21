<?php

declare(strict_types=1);

use Codefy\Framework\Security\Firewall\FirewallExclusionPolicy;
use Codefy\Framework\Security\Firewall\NullThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatDetector;
use Codefy\Framework\Security\Firewall\ThreatPatternRegistry;
use Psr\Http\Message\ServerRequestInterface;

beforeEach(function (): void {
    $this->detector = new ThreatDetector(
        new ThreatPatternRegistry(codefy()->make('codefy.config')),
        new FirewallExclusionPolicy(codefy()->make('codefy.config')),
        new NullThreatLogger()
    );;
});

function sqlite_admin_request(
    string $method = 'POST',
    string $path = '/admin/sqlite-admin/query'
): ServerRequestInterface {
    $baseRequest = codefy()->request;

    return $baseRequest
            ->withMethod($method)
            ->withUri(
                    $baseRequest->getUri()
                            ->withPath($path)
                            ->withQuery('')
            );
}

it('allows SQL in the excluded SQLiteAdmin SQL field', function (
        string $sql
): void {
    $request = sqlite_admin_request()
            ->withParsedBody([
                    'sql' => $sql,
            ]);

    expect($this->detector->detect($request))->toBeNull();
})->with([
        'select' => ['SELECT * FROM cms_post'],
        'insert' => [
                "INSERT INTO cms_option (option_key) VALUES ('site_name')",
        ],
        'update' => [
                "UPDATE cms_post SET post_title = 'Updated' WHERE post_id = 1",
        ],
        'delete' => [
                'DELETE FROM cms_post WHERE post_id = 1',
        ],
        'alter' => [
                'ALTER TABLE cms_post ADD COLUMN test TEXT',
        ],
        'drop' => [
                'DROP TABLE temporary_import',
        ],
]);

it('does not apply the SQLiteAdmin exclusion to another route', function (): void {
    $request = sqlite_admin_request(
            path: '/admin/content/save'
    )->withParsedBody([
            'sql' => 'SELECT * FROM cms_user',
    ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('sql_injection')
            ->and($match->excluded)->toBeFalse();
});

it('does not apply the POST exclusion to GET requests', function (): void {
    $request = sqlite_admin_request(
            method: 'GET'
    )->withQueryParams([
            'sql' => 'SELECT * FROM cms_user',
    ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('sql_injection');
});

it('does not exclude a different body field', function (): void {
    $request = sqlite_admin_request()
            ->withParsedBody([
                    'search' => 'SELECT * FROM cms_user',
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('sql_injection')
            ->and($match->field)->toBe('search');
});

it('continues to detect XSS on the SQLiteAdmin route', function (): void {
    $request = sqlite_admin_request()
            ->withParsedBody([
                    'sql' => 'SELECT * FROM cms_post',
                    'title' => '<script>alert(1)</script>',
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('xss')
            ->and($match->field)->toBe('title');
});

it('continues to detect file traversal on the SQLiteAdmin route', function (): void {
    $request = sqlite_admin_request()
            ->withQueryParams([
                    'database' => '../../config/database.php',
            ])
            ->withParsedBody([
                    'sql' => 'SELECT * FROM cms_post',
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('file_traversal')
            ->and($match->field)->toBe('database');
});

it('continues to detect SSRF on the SQLiteAdmin route', function (): void {
    $request = sqlite_admin_request()
            ->withParsedBody([
                    'sql' => 'SELECT * FROM cms_post',
                    'import_url' => 'http://169.254.169.254/latest/meta-data',
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('ssrf')
            ->and($match->field)->toBe('import_url');
});

it('continues to detect RCE inside the SQL field unless excluded', function (): void {
    $request = sqlite_admin_request()
            ->withParsedBody([
                    'sql' => 'SELECT system("id")',
            ]);

    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('rce')
            ->and($match->field)->toBe('sql');
});

it('applies nested-field exclusions only to matching fields', function (): void {
    $request = sqlite_admin_request()
            ->withParsedBody([
                    'editor' => [
                            'sql' => 'SELECT * FROM cms_post',
                    ],
            ]);

    /*
     * This should be detected unless the configured field is
     * "editor.sql" or a wildcard such as "editor.*".
     */
    $match = $this->detector->detect($request);

    expect($match)
            ->not->toBeNull()
            ->and($match->group)->toBe('sql_injection')
            ->and($match->field)->toBe('editor.sql');
});
