<?php

declare(strict_types=1);

namespace Codefy\Framework\Pipeline;

final class PipelineFactory
{
    /**
     * @param iterable<callable> $pipes
     * @return Chainable
     */
    public function create(iterable $pipes): Chainable
    {
        $builder = new PipelineBuilder();
        foreach ($pipes as $pipe) {
            $builder->pipe($pipe);
        }

        return $builder->build();
    }
}
