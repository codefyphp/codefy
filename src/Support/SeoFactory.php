<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Melbahja\Seo\Indexing;
use Melbahja\Seo\Interfaces\SchemaInterface;
use Melbahja\Seo\MetaTags;
use Melbahja\Seo\Ping;
use Melbahja\Seo\Robots;
use Melbahja\Seo\Schema;
use Melbahja\Seo\Schema\Thing;
use Melbahja\Seo\Sitemap;

final class SeoFactory
{
    /**
     * @param string $type
     * @param array $data
     * @return Thing
     */
    public static function thing(string $type, array $data = []): Thing
    {
        return new Thing($type, $data);
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
     * @param string $domain
     * @param array $options
     * @return Sitemap
     */
    public static function sitemap(string $domain, array $options = []): Sitemap
    {
        return new Sitemap($domain, $options);
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
     * Initialize new sitemap ping.
     *
     * @param array $append
     * @return Ping
     */
    public static function ping(array $append = []): Ping
    {
        return new Ping($append);
    }

    /**
     * Initialize indexer.
     *
     * @param string $host
     * @param array $keys
     * @return Indexing
     */
    public static function indexing(string $host, array $keys): Indexing
    {
        return new Indexing(host: $host, keys: $keys);
    }
}
