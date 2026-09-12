<?php

declare(strict_types=1);

use Codefy\Framework\Support\SeoFactory;
use Melbahja\Seo\Exceptions\SeoException;
use Melbahja\Seo\Indexing\IndexNowEngine;
use Melbahja\Seo\Indexing\URLIndexingType;
use Melbahja\Seo\Sitemap\OutputMode;
use Melbahja\Seo\Utils\HttpClient;

it('builds v3 schema things with nested properties and numeric values', function () {
    $product = SeoFactory::thing(type: 'Product', data: [
        'name' => 'Example',
        'offers' => SeoFactory::thing('Offer', ['price' => 12.5, 'priceCurrency' => 'USD']),
    ]);
    $schema = json_decode(json_encode(SeoFactory::schema($product), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    expect($schema['@type'])->toBe('Product')
        ->and($schema['offers']['@type'])->toBe('Offer')
        ->and($schema['offers']['price'])->toBe(12.5);
    $graph = SeoFactory::schema($product, SeoFactory::thing('Organization', ['name' => 'Publisher']));
    expect($graph->jsonSerialize()['@graph'])->toHaveCount(2);
});

it('renders v3 meta tags and robots directives', function () {
    $meta = SeoFactory::metaTags()->title('Example')->canonical('https://example.test');
    $robots = SeoFactory::robots()->addRule('*', ['/private'])->addSitemap('https://example.test/sitemap.xml');
    expect((string) $meta)->toContain('<title>Example</title>')->toContain('https://example.test')
        ->and((string) $robots)->toContain('User-agent: *')->toContain('Disallow: /private')
        ->toContain('Sitemap: https://example.test/sitemap.xml');
});

it('renders a sitemap index and URL set in memory with v3 options', function () {
    $sitemap = SeoFactory::sitemap(
        baseUrl: 'https://example.test',
        sitemapBaseUrl: 'https://static.example.test/maps',
        mode: OutputMode::MEMORY,
        indent: null,
        hideGenerator: true
    );
    $sitemap->links('pages.xml', ['/first', '/second']);
    $index = $sitemap->render();
    $pages = $sitemap->generate('pages.xml')->render();
    expect($index)->toContain('https://static.example.test/maps/pages.xml')
        ->and($pages)->toContain('https://example.test/first')->toContain('https://example.test/second');
    expect(simplexml_load_string($index))->not->toBeFalse()
        ->and(simplexml_load_string($pages))->not->toBeFalse();
});

it('writes sitemaps with an explicit output directory and index filename', function (OutputMode $mode) {
    $directory = sys_get_temp_dir() . '/codefy-seo-' . bin2hex(random_bytes(8));
    mkdir($directory, 0700);
    try {
        $sitemap = SeoFactory::sitemap(
            baseUrl: 'https://example.test', saveDir: $directory,
            indexName: 'index.xml', mode: $mode, hideGenerator: true, dateFormat: 'Y-m-d'
        );
        $sitemap->links('pages.xml', ['/page']);
        expect($sitemap->render())->toBeTrue()
            ->and(file_get_contents($directory . '/index.xml'))->toContain('https://example.test/pages.xml')
            ->and(file_get_contents($directory . '/pages.xml'))->toContain('https://example.test/page');
    } finally {
        foreach (glob($directory . '/*') as $file) { unlink($file); }
        rmdir($directory);
    }
})->with([OutputMode::FILE, OutputMode::TEMP]);

it('builds separate IndexNow and Google clients and submits through injected HTTP clients', function () {
    $client = new class extends HttpClient {
        public array $requests = [];
        public function request(string $method, string $url, $body = null, array $headers = []): ?string
        {
            $this->requests[] = [$method, $url, $body];
            return '{}';
        }
        public function getStatusCode(): int { return 200; }
    };
    $indexNow = SeoFactory::indexNow('test-key', $client);
    $google = SeoFactory::googleIndexer('test-token', $client);
    expect($client->requests)->toBe([]);
    expect($indexNow->submitUrl('https://example.test/page', IndexNowEngine::BING))->toBeTrue()
        ->and($google->submitUrl('https://example.test/old', URLIndexingType::DELETE))->toBeTrue();
    expect($client->requests[0])->toBe([
        'GET', IndexNowEngine::BING->toUrl('https://example.test/page', 'test-key'), null,
    ])->and($client->requests[1])->toBe([
        'POST', 'https://indexing.googleapis.com/v3/urlNotifications:publish',
        ['url' => 'https://example.test/old', 'type' => 'URL_DELETED'],
    ]);
});

it('rejects empty indexing credentials', function () {
    expect(fn () => SeoFactory::indexNow(''))->toThrow(SeoException::class);
    expect(fn () => SeoFactory::googleIndexer(''))->toThrow(SeoException::class);
});
