<?php

declare(strict_types=1);

namespace Codefy\Framework\Pipeline;

use Codefy\Framework\Proxy\Codefy;

final class PipelineBuilder
{
    // @phpstan-ignore property.onlyWritten
    private array $pipes = [];

    /**
     * @param callable $pipe
     * @return self
     */
    public function pipe(callable $pipe): self
    {
        $pipeline = clone $this;
        $pipeline->pipes[] = $pipe;

        return $pipeline;
    }

    public function build(): Chainable
    {
        return new Pipeline(Codefy::$PHP->getContainer());
    }
}
