<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Melbahja\Seo\Exceptions\SeoException;
use Melbahja\Seo\Indexing\GoogleIndexer;
use Melbahja\Seo\Indexing\IndexNowIndexer;
use Melbahja\Seo\Interfaces\SchemaInterface;
use Melbahja\Seo\MetaTags;
use Melbahja\Seo\Robots;
use Melbahja\Seo\Schema;
use Melbahja\Seo\Schema\Thing;
use Melbahja\Seo\Sitemap;
use Melbahja\Seo\Sitemap\OutputMode;
use Melbahja\Seo\Utils\HttpClient;

final class SeoFactory
{
    /**
     * @param string $type
     * @param array<string, mixed> $data
     * @return Thing
     */
    public static function thing(string $type, array $data = []): Thing
    {
        return new Thing(props: $data, type: $type);
    }

    /**
     * @param SchemaInterface ...$things
     * @return Schema
     */
    public static function schema(SchemaInterface ...$things): Schema
    {
        return new Schema(...$things);
    }

    /**
     * Initialize new meta tags builder.
     *
     * @return MetaTags
     */
    public static function metaTags(): MetaTags
    {
        return new MetaTags();
    }

    /**
     * Initialize new sitemap builder.
     *
     * @param string $baseUrl
     * @param string|null $saveDir
     * @param string $indexName
     * @param string|null $sitemapBaseUrl
     * @param OutputMode $mode
     * @param string|null $indent
     * @param bool $hideGenerator
     * @param string $dateFormat
     * @return Sitemap
     */
    public static function sitemap(
        string $baseUrl,
        ?string $saveDir = null,
        string $indexName = 'sitemap.xml',
        ?string $sitemapBaseUrl = null,
        OutputMode $mode = OutputMode::TEMP,
        ?string $indent = ' ',
        bool $hideGenerator = false,
        string $dateFormat = 'c'
    ): Sitemap {
        return new Sitemap(
            baseUrl: $baseUrl,
            saveDir: $saveDir,
            indexName: $indexName,
            sitemapBaseUrl: $sitemapBaseUrl,
            mode: $mode,
            indent: $indent,
            hideGenerator: $hideGenerator,
            dateFormat: $dateFormat
        );
    }

    /**
     * Generate robots.txt.
     *
     * @return Robots
     */
    public static function robots(): Robots
    {
        return new Robots();
    }

    /**
     * Initialize an IndexNow client. Construction does not submit URLs.
     *
     * @throws SeoException
     */
    public static function indexNow(
        #[\SensitiveParameter] string $apiKey,
        ?HttpClient $httpClient = null
    ): IndexNowIndexer {
        return new IndexNowIndexer(apiKey: $apiKey, httpClient: $httpClient);
    }

    /**
     * Initialize a Google Indexing client with an OAuth access token.
     *
     * @throws SeoException
     */
    public static function googleIndexer(
        #[\SensitiveParameter] string $accessToken,
        ?HttpClient $httpClient = null
    ): GoogleIndexer {
        return new GoogleIndexer(accessToken: $accessToken, httpClient: $httpClient);
    }
}
