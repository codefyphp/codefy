<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Cocur\Slugify\Slugify;
use Codefy\Framework\Support\RequestMethod;
use Laminas\Diactoros\Stream;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Cache\Psr6\Item;

use function md5;
use function trim;

class ContentCacheMiddleware implements MiddlewareInterface
{
    public function __construct(protected CacheItemPoolInterface $cacheItemPool)
    {
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (RequestMethod::GET !== $request->getMethod()) {
            return $response;
        }

        if ($html = $this->getCachedResponseHtml($request)) {
            return $this->buildResponse($html, $response);
        }

        if (200 === $response->getStatusCode()) {
            $this->cacheResponse($request, $response);
        }

        return $response;
    }

    protected function buildResponse(string $html, ResponseInterface $response): ResponseInterface
    {
        $body = new Stream('php://memory', 'w');
        $body->write($html);

        return $response->withBody($body);
    }

    protected function createKeyFromRequest(RequestInterface $request): string
    {
        $cacheItemKeyFactory = function (RequestInterface $request): string {
            static $key = null;
            if (null === $key) {
                $uri = $request->getUri();
                $slugify = new Slugify();
                $key = $slugify->slugify(
                    string: trim(
                        string: $uri->getPath(),
                        characters: '/'
                    ) . ($uri->getQuery() ? '?' . $uri->getQuery() : '')
                );
            }

            return md5($key);
        };

        return $cacheItemKeyFactory($request);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getCachedResponseHtml(RequestInterface $request): mixed
    {
        return $this
            ->cacheItemPool
            ->getItem($this->createKeyFromRequest($request))
            ->get();
    }

    protected function cacheResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $cacheItem = new Item($this->createKeyFromRequest($request));
        $value = (string) $response->getBody();
        $cacheItem->set($value);

        $this
            ->cacheItemPool
            ->save($cacheItem);
    }
}
