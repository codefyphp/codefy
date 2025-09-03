<?php

declare(ticks=1);

namespace Codefy\Framework\Http\Emitter;

use Psr\Http\Message\ResponseInterface;

final class SapiEmitter extends BaseEmitter
{
    /**
     * {@inheritDoc}
     */
    public function emit(ResponseInterface $response): void
    {
        $this->assertNoPreviousOutput();
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);
        $this->closeConnection();
    }

    /**
     * Emit the response body
     *
     * @param ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response): void
    {
        echo $response->getBody();
    }
}
