<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Emitter;

use Psr\Http\Message\ResponseInterface;

use function count;
use function ob_end_clean;
use function ob_end_flush;
use function ob_get_status;

use const PHP_OUTPUT_HANDLER_CLEANABLE;
use const PHP_OUTPUT_HANDLER_FLUSHABLE;
use const PHP_OUTPUT_HANDLER_REMOVABLE;

class HttpUtil
{
    /**
     * Private constructor; non-instantiable.
     */
    private function __construct()
    {
    }

    /**
     * Inject the Content-Length header if is not already present.
     */
    public static function injectContentLength(ResponseInterface $response): ResponseInterface
    {
        if ($response->hasHeader('Content-Length')) {
            return $response;
        }

        $responseBody = $response->getBody();

        // PSR-7 indicates int OR null for the stream size; for null values,
        // we will not auto-inject the Content-Length.
        if ($responseBody->getSize() !== null) {
            $response = $response->withHeader('Content-Length', (string) $responseBody->getSize());
        }

        return $response;
    }

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * Resulting level can be greater than target level if
     * a non-removable buffer has been encountered.
     *
     * @param int  $maxBufferLevel The target output buffering level
     * @param bool $flush          Whether to flush or clean the buffers
     */
    public static function closeOutputBuffers(int $maxBufferLevel, bool $flush): void
    {
        $status = ob_get_status(full_status: true);
        $level = count($status);
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

        while (
                $level-- > $maxBufferLevel
                && isset($status[$level])
                && ($status[$level]['del'] ?? ! isset($status[$level]['flags'])
                || $flags === ($status[$level]['flags'] & $flags))
        ) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }
}
