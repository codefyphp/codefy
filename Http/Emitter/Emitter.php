<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Emitter;

use Psr\Http\Message\ResponseInterface;
use Codefy\Framework\Http\Emitter\Exceptions\EmitterException;

interface Emitter
{
    /**
     * Emit a response.
     *
     * Emits a response, including status line, headers, and the message body,
     * according to the environment.
     *
     * Implementations of this method may be written in such a way as to have
     * side effects, such as usage of header() or pushing output to the
     * output buffer.
     *
     * Implementations MAY raise exceptions if they are unable to emit the
     * response; e.g., if headers have already been sent.
     *
     * @param ResponseInterface $response
     * @throws EmitterException
     * @return void
     */
    public function emit(ResponseInterface $response): void;
}
