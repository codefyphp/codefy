<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(protected ConfigContainer $configContainer)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $origins = $this->values('access-control-allow-origin');
        $credentials = $this->values('access-control-allow-credentials') === ['true'];
        $wildcard = in_array('*', $origins, true);

        if ($wildcard && $credentials) {
            throw new \InvalidArgumentException('Credentialed CORS requires an explicit origin allowlist.');
        }

        $preflight = $request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
        $allowed = $origin !== '' && ($wildcard || in_array($origin, $origins, true));
        $methods = $this->values('access-control-allow-methods');
        $headers = $this->values('access-control-allow-headers');

        if ($preflight && $origin !== '') {
            $requestedHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
            $requestedHeaders = $requestedHeaders === '' ? [] : array_map('trim', explode(',', $requestedHeaders));
            $allowed = $allowed
            && in_array($request->getHeaderLine('Access-Control-Request-Method'), $methods, true)
            && array_all($requestedHeaders, static fn (string $header): bool =>
                    in_array(strtolower($header), array_map('strtolower', $headers), true));
            $response = new Response(status: $allowed ? 204 : 403);
        } else {
            $response = $handler->handle($request);
        }

        // This middleware owns the CORS policy; do not retain conflicting downstream grants.
        foreach (array_keys($response->getHeaders()) as $header) {
            if (str_starts_with(strtolower($header), 'access-control-')) {
                $response = $response->withoutHeader($header);
            }
        }

        $response = $this->vary($response, 'Origin');
        if ($preflight) {
            $response = $this->vary($response, 'Access-Control-Request-Method');
            $response = $this->vary($response, 'Access-Control-Request-Headers');
        }
        if (!$allowed) {
            return $response;
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $wildcard ? '*' : $origin);
        if ($credentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        if ($preflight) {
            $response = $response
                ->withHeader('Access-Control-Allow-Methods', implode(', ', $methods))
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $headers));
            $maxAge = $this->values('access-control-max-age');
            if ($maxAge !== []) {
                $response = $response->withHeader('Access-Control-Max-Age', $maxAge);
            }
        } elseif (($exposed = $this->values('access-control-expose-headers')) !== []) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $exposed));
        }

        return $response;
    }

    /** @return list<string> */
    private function values(string $key): array
    {
        $value = $this->configContainer->getConfigKey('cors.' . $key, []);
        if (is_bool($value)) {
            return [$value ? 'true' : 'false'];
        }
        return array_map(static fn (mixed $item): string => (string) $item, (array) $value);
    }

    private function vary(ResponseInterface $response, string $header): ResponseInterface
    {
        $vary = array_map('strtolower', array_map('trim', explode(',', $response->getHeaderLine('Vary'))));
        return in_array('*', $vary, true) || in_array(strtolower($header), $vary, true)
        ? $response
        : $response->withAddedHeader('Vary', $header);
    }
}
