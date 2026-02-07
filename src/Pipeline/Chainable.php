<?php

declare(strict_types=1);

namespace Codefy\Framework\Pipeline;

interface Chainable
{
    /**
     * Set the object being sent through the pipeline.
     *
     * @param mixed $passable
     * @return self
     */
    public function send(mixed $passable): self;

    /**
     * Set the array of pipes.
     *
     * @param mixed $pipes
     * @return self
     */
    public function through(mixed $pipes): self;

    /**
     * Set the method to call on the pipes.
     *
     * @param string $method
     * @return self
     */
    public function via(string $method): self;

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param \Closure $destination
     * @return mixed
     */
    public function then(\Closure $destination): mixed;

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function thenReturn(): mixed;

    /**
     * Run a single pipe.
     *
     * @param class-string $pipe
     */
    public function run(string $pipe, mixed $data = true): mixed;
}
