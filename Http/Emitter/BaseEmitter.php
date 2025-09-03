<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Emitter;

use Psr\Http\Message\ResponseInterface;
use Codefy\Framework\Http\Emitter\Traits\EmitterTraitAware;

abstract class BaseEmitter implements Emitter
{
    use EmitterTraitAware;

    /**
     * {@inheritDoc}
     */
    abstract public function emit(ResponseInterface $response): void;
}
