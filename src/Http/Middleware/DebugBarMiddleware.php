<?php

namespace Codefy\Framework\Http\Middleware;

use DebugBar\JavascriptRenderer;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function file_exists;
use function fopen;
use function htmlspecialchars;
use function implode;
use function in_array;
use function pathinfo;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function ucwords;

use const PATHINFO_EXTENSION;
use const SEEK_END;

class DebugBarMiddleware implements MiddlewareInterface
{
    public const string FORCE_KEY = 'X-Enable-Debug-Bar';

    /**
     * @var JavascriptRenderer
     */
    private JavascriptRenderer $debugBarRenderer;

    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    /**
     * @var StreamFactoryInterface|null
     */
    private ?StreamFactoryInterface $streamFactory = null;

    public function __construct(
        JavascriptRenderer $debugBarRenderer,
        ResponseFactoryInterface $responseFactory,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->debugBarRenderer = $debugBarRenderer;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory ?? new StreamFactory();
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($staticFile = $this->getStaticFile($request->getUri())) {
            return $staticFile;
        }

        $response = $handler->handle($request);

        if ($this->shouldReturnResponse($request, $response)) {
            return $response;
        }

        if ($this->isHtmlResponse($response)) {
            return $this->attachDebugBarToHtmlResponse($response);
        }

        return $this->prepareHtmlResponseWithDebugBar($response);
    }

    private function shouldReturnResponse(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        $forceHeaderValue = $request->getHeaderLine(self::FORCE_KEY);
        $forceCookieValue = $request->getCookieParams()[self::FORCE_KEY] ?? '';
        $forceAttributeValue = $request->getAttribute(self::FORCE_KEY, '');
        $isForceEnable = in_array('true', [$forceHeaderValue, $forceCookieValue, $forceAttributeValue], true);
        $isForceDisable = in_array('false', [$forceHeaderValue, $forceCookieValue, $forceAttributeValue], true);

        return $isForceDisable
        || (!$isForceEnable && ($this->isRedirect($response) || !$this->isHtmlAccepted($request)));
    }

    private function prepareHtmlResponseWithDebugBar(ResponseInterface $response): ResponseInterface
    {
        $head = $this->debugBarRenderer->renderHead();
        $body = $this->debugBarRenderer->render();
        $outResponseBody = $this->serializeResponse($response);
        $template = '<html><head>%s</head><body><h1>DebugBar</h1><p>Response:</p><pre>%s</pre>%s</body></html>';
        $escapedOutResponseBody = htmlspecialchars($outResponseBody);
        $result = sprintf($template, $head, $escapedOutResponseBody, $body);

        $stream = $this->streamFactory->createStream($result);

        return $this->responseFactory->createResponse()
                ->withBody($stream)
                ->withAddedHeader('Content-type', 'text/html');
    }

    private function attachDebugBarToHtmlResponse(ResponseInterface $response): ResponseInterface
    {
        $head = $this->debugBarRenderer->renderHead();
        $body = $this->debugBarRenderer->render();
        $responseBody = $response->getBody();

        if (! $responseBody->eof() && $responseBody->isSeekable()) {
            $responseBody->seek(0, SEEK_END);
        }
        $responseBody->write($head . $body);

        return $response;
    }

    private function getStaticFile(UriInterface $uri): ?ResponseInterface
    {
        $path = $this->extractPath($uri);

        if (!str_starts_with($path, $this->debugBarRenderer->getBaseUrl())) {
            return null;
        }

        $pathToFile = substr($path, strlen($this->debugBarRenderer->getBaseUrl()));

        $fullPathToFile = $this->debugBarRenderer->getBasePath() . $pathToFile;

        if (!file_exists($fullPathToFile)) {
            return null;
        }

        $contentType = $this->getContentTypeByFileName($fullPathToFile);
        $stream = $this->streamFactory->createStreamFromResource(fopen($fullPathToFile, 'rb'));

        return $this->responseFactory->createResponse()
                ->withBody($stream)
                ->withAddedHeader('Content-type', $contentType);
    }

    private function extractPath(UriInterface $uri): string
    {
        return $uri->getPath();
    }

    private function getContentTypeByFileName(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $map = [
                'css' => 'text/css',
                'js' => 'text/javascript',
                'otf' => 'font/opentype',
                'eot' => 'application/vnd.ms-fontobject',
                'svg' => 'image/svg+xml',
                'ttf' => 'application/font-sfnt',
                'woff' => 'application/font-woff',
                'woff2' => 'application/font-woff2',
        ];

        return $map[$ext] ?? 'text/plain';
    }

    private function isHtmlResponse(ResponseInterface $response): bool
    {
        return $this->isHtml($response, 'Content-Type');
    }

    private function isHtmlAccepted(ServerRequestInterface $request): bool
    {
        return $this->isHtml($request, 'Accept');
    }

    private function isHtml(MessageInterface $message, string $headerName): bool
    {
        return str_contains($message->getHeaderLine($headerName), 'text/html');
    }

    private function isRedirect(ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();

        return $statusCode >= 300 && $statusCode < 400 && $response->getHeaderLine('Location') !== '';
    }

    private function serializeResponse(ResponseInterface $response): string
    {
        $reasonPhrase = $response->getReasonPhrase();
        $headers      = $this->serializeHeaders($response->getHeaders());
        $format       = 'HTTP/%s %d%s%s%s';

        if (! empty($headers)) {
            $headers = "\r\n" . $headers;
        }

        $headers .= "\r\n\r\n";

        return sprintf(
            $format,
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : ''),
            $headers,
            $response->getBody()
        );
    }

    /**
     * @param array<string, array<string>> $headers
     */
    private function serializeHeaders(array $headers): string
    {
        $lines = [];
        foreach ($headers as $header => $values) {
            $normalized = $this->filterHeader($header);
            foreach ($values as $value) {
                $lines[] = sprintf('%s: %s', $normalized, $value);
            }
        }

        return implode("\r\n", $lines);
    }

    private function filterHeader(string $header): string
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}
