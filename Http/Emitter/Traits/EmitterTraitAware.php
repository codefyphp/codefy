<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Emitter\Traits;

use Psr\Http\Message\ResponseInterface;
use Codefy\Framework\Http\Emitter\Exceptions\HeadersAlreadySentException;
use Codefy\Framework\Http\Emitter\Exceptions\PreviousOutputException;
use Codefy\Framework\Http\Emitter\HttpUtil;

use function assert;
use function fastcgi_finish_request;
use function function_exists;
use function header;
use function headers_sent;
use function in_array;
use function is_string;
use function ob_get_length;
use function ob_get_level;
use function sprintf;
use function ucwords;

use const PHP_SAPI;

trait EmitterTraitAware
{
    /**
     * Assert either that no headers have been sent
     * or the output buffer contains no content.
     *
     * @return void
     */
    protected function assertNoPreviousOutput(): void
    {
        $file = null;
        $line = null;

        if (headers_sent($file, $line)) {
            throw new HeadersAlreadySentException(headersSentFile: (string)$file, headersSentLine: (int)$line);
        }

        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw new PreviousOutputException();
        }
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is available, it, too, is emitted.
     *
     * @param ResponseInterface $response
     * @return void
     */
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        $statusCode   = $response->getStatusCode();

        $this->header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            $reasonPhrase ? ' ' . $reasonPhrase : ''
        ), true, $statusCode);
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     *
     * @param ResponseInterface $response
     * @return void
     */
    protected function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            assert(is_string($header));
            $name  = $this->normalizeHeaderName(headerName: $header);
            $first = $name !== 'Set-Cookie';

            foreach ($values as $value) {
                $this->header(sprintf(
                    '%s: %s',
                    $name,
                    $value
                ), $first, $statusCode);
                $first = false;
            }
        }
    }

    /**
     * Normalize a header name
     *
     * Normalized header will be in the following format: Example-Header-Name
     *
     * @param string $headerName
     * @return string
     */
    private function normalizeHeaderName(string $headerName): string
    {
        return ucwords(string: $headerName, separators: '-');
    }

    private function header(string $headerName, bool $replace, int $statusCode): void
    {
        header(header: $headerName, replace: $replace, response_code: $statusCode);
    }

    protected function closeConnection(): void
    {
        if (! in_array(needle: PHP_SAPI, haystack: ['cli', 'phpdbg'], strict: true)) {
            HttpUtil::closeOutputBuffers(maxBufferLevel: 0, flush: true);
        }

        if (function_exists(function: 'fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
