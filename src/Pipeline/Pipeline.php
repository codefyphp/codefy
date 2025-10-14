<?php

declare(strict_types=1);

namespace Codefy\Framework\Pipeline;

use Closure;
use Codefy\Framework\Support\Traits\DbTransactionsAware;
use Exception;
use Qubus\EventDispatcher\ActionFilter\Traits\ActionAware;
use Qubus\Injector\ServiceContainer;
use RuntimeException;
use Throwable;

final class Pipeline implements Chainable
{
    use ActionAware;
    use DbTransactionsAware;

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected mixed $passable = null;

    /**
     * The callback to be executed on failure pipeline.
     *
     * @var Closure|null
     */
    protected ?Closure $onFailure = null;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected array $pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected string $method = 'handle';

    /**
     * The final callback to be executed after the pipeline ends regardless of the outcome.
     *
     * @var Closure|null
     */
    protected ?Closure $finally = null;

    public function __construct(protected ServiceContainer $container)
    {
    }

    /**
     * @inheritDoc
     */
    public function send(mixed $passable): Chainable
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function through(mixed $pipes): Chainable
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Push additional pipes onto the pipeline.
     *
     * @param callable $pipe
     * @return self
     */
    public function pipe(callable $pipe): self
    {
        $pipeline = clone $this;
        $pipeline->pipes[] = $pipe;

        return $pipeline;
    }

    /**
     * @inheritDoc
     */
    public function via(string $method): Chainable
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws Throwable
     */
    public function then(Closure $destination): mixed
    {
        try {
            $this->doAction(
                'pipeline_started',
                $destination,
                $this->passable,
                $this->pipes(),
                $this->useTransaction,
            );

            $this->beginTransaction();

            $pipeline = array_reduce(
                array_reverse($this->pipes()),
                $this->carry(),
                $this->prepareDestination($destination)
            );

            $result = $pipeline($this->passable);

            $this->commitTransaction();

            $this->doAction(
                'pipeline_finished',
                $destination,
                $this->passable,
                $this->pipes(),
                $this->useTransaction,
                $result,
            );

            return $result;
        } catch (Throwable $e) {
            $this->rollbackTransaction();

            if ($this->onFailure) {
                return ($this->onFailure)($this->passable, $e);
            }

            return $this->handleException($this->passable, $e);
        } finally {
            if ($this->finally) {
                ($this->finally)($this->passable);
            }
        }
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function thenReturn(): mixed
    {
        return $this->then(fn ($passable) => $passable);
    }

    /**
     * Set a final callback to be executed after the pipeline ends regardless of the outcome.
     *
     * @param  Closure  $callback
     * @return self
     */
    public function finally(Closure $callback): self
    {
        $this->finally = $callback;

        return $this;
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param Closure $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                $this->doAction('pipeline_execution_started', $pipe, $passable);

                if (is_callable($pipe)) {
                    // If the pipe is a callable, then we will call it directly, but otherwise we
                    // will resolve the pipes out of the dependency container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    $result = $pipe($passable, $stack);

                    $this->doAction('pipeline_execution_finished', $pipe, $passable);

                    return $result;
                } elseif (! is_object($pipe)) {
                    [$name, $parameters] = $this->parsePipeString($pipe);

                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    $pipe = $this->getContainer()->make($name);

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    // If the pipe is already an object we'll just make a callable and pass it to
                    // the pipe as-is. There is no need to do any extra parsing and formatting
                    // since the object we're given was already a fully instantiated object.
                    $parameters = [$passable, $stack];
                }

                $carry = method_exists($pipe, $this->method)
                ? $pipe->{$this->method}(...$parameters)
                : $pipe(...$parameters);

                $this->doAction('pipeline_execution_finished', $pipe, $passable);

                return $this->handleCarry($carry);
            };
        };
    }

    /**
     * Get the container instance.
     *
     * @return ServiceContainer|null
     *
     */
    protected function getContainer(): ?ServiceContainer
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }

    /**
     * Set callback to be executed on failure pipeline.
     *
     * @param Closure $callback
     * @return self
     */
    public function onFailure(Closure $callback): self
    {
        $this->onFailure = $callback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function run(string $pipe, mixed $data = true): mixed
    {
        return $this
            ->send($data)
            ->through([$pipe])
            ->thenReturn();
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string  $pipe
     * @return array
     */
    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * Get the array of configured pipes.
     *
     * @return array
     */
    protected function pipes(): array
    {
        return $this->pipes;
    }

    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handleCarry(mixed $carry): mixed
    {
        return $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param mixed $passable
     * @param Throwable $e
     * @return mixed
     *
     * @throws Throwable
     */
    protected function handleException(mixed $passable, Throwable $e): mixed
    {
        throw $e;
    }
}
