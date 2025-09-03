<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Emitter;

use Psr\Http\Message\ResponseInterface;
use Codefy\Framework\Http\Emitter\Exceptions\EmitterException;

use function connection_status;
use function flush;
use function preg_match;
use function strlen;
use function substr;

use const CONNECTION_NORMAL;

final class SapiStreamEmitter extends BaseEmitter
{
    private const string CONTENT_PATTERN_REGEX = '/(?P<unit>[\w]+)\s+(?P<start>\d+)-(?P<end>\d+)\/(?P<size>\d+|\*)/';

    /**
     * Maximum output buffering size for each iteration.
     */
    protected int $maxBufferSize = 8192;


    /**
     * Get the value of max buffer size
     *
     * @return integer
     */
    public function getMaxBufferSize(): int
    {
        return $this->maxBufferSize;
    }

    /**
     * Set the value of max buffer size
     *
     * @param int $maxBufferSize
     * @return SapiStreamEmitter
     * @throws EmitterException
     */
    public function setMaxBufferSize(int $maxBufferSize): SapiStreamEmitter
    {
        if (!$maxBufferSize < 1) {
            throw new EmitterException('Buffer size must be a positive integer');
        }

        $this->maxBufferSize = $maxBufferSize;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(ResponseInterface $response): void
    {
        $this->assertNoPreviousOutput();
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        flush();
        $this->emitStream($response);
        $this->closeConnection();
    }

    /**
     * Emit response body as a stream
     *
     * @param ResponseInterface $response
     * @return void
     */
    private function emitStream(ResponseInterface $response): void
    {
        $range = $this->getContentRange($response);

        if ($range && $range->getUnit() == 'bytes') {
            $this->emitBodyRange($response, $range);
            return;
        }

        $this->emitBody($response);
    }

    /**
     * Emit the response body by max buffer size
     *
     * @param ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response): void
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (!$body->isReadable()) {
            echo $body;
            return;
        }

        while (!$body->eof()) {
            echo $body->read($this->getMaxBufferSize());

            if (connection_status() != CONNECTION_NORMAL) {
                // Connection is broken
                // Stop emitting the rest of the stream
                break;
            }
        }
    }

    /**
     * Emit the range of the response body by max buffer size.
     *
     * @param ResponseInterface $response
     * @param ContentRange $range
     * @return void
     */
    private function emitBodyRange(
        ResponseInterface $response,
        ContentRange $range
    ): void {
        $start = $range->getStart();
        $end = $range->getEnd();

        $body = $response->getBody();
        $length = $end - $start + 1;

        if ($body->isSeekable()) {
            $body->seek($start);
            $start = 0;
        }

        if (!$body->isReadable()) {
            echo substr($body->getContents(), $start, $length);
            return;
        }

        $remaining = $length;

        while ($remaining > 0 && !$body->eof()) {
            $contents = $body->read(
                $remaining >= $this->getMaxBufferSize()
                    ? $this->getMaxBufferSize()
                    : $remaining
            );

            echo $contents;

            if (connection_status() != CONNECTION_NORMAL) {
                // Connection is broken
                // Stop emitting the rest of the stream
                break;
            }

            $remaining -= strlen($contents);
        }
    }

    /**
     * Get ContentRange
     *
     * Parses the Content-Range header line from the response and generates
     * ContentRange instance.
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16
     *
     * @param ResponseInterface $response
     * @return ContentRange|null
     */
    private function getContentRange(ResponseInterface $response): ?ContentRange
    {
        $headerLine = $response->getHeaderLine('Content-Range');

        if (
                !$headerLine
                || !preg_match(self::CONTENT_PATTERN_REGEX, $headerLine, $matches)
        ) {
            return null;
        }

        return new ContentRange(
            (int) $matches['start'],
            (int) $matches['end'],
            $matches['size'] === '*' ? null : (int) $matches['size'],
            $matches['unit']
        );
    }
}
