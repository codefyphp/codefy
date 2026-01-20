<?php

declare(strict_types=1);

namespace Codefy\Framework\Traits;

use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\HttpExceptionFactory;
use Throwable;

trait ThrowableTransformAware
{
    /**
     * A map of Throwable::class => callable(Throwable): HttpException
     *
     * @var array<class-string<Throwable>, callable(Throwable): HttpException>
     */
    protected array $exceptionMap = [];

    /**
     * Register a transformer for a specific Throwable class.
     *
     * Example:
     *   $this->mapException(RecordsNotFoundException::class, fn($e) => new NotFoundHttpException('/x'));
     */
    public function mapException(string $class, callable $transformer): void
    {
        $this->exceptionMap[$class] = $transformer;
    }

    /**
     * Convert a Throwable into an HttpException if a mapping exists.
     * Otherwise, return null.
     *
     * @param Throwable $t
     * @return HttpException|null
     */
    protected function tryTransformException(Throwable $t): ?HttpException
    {
        // Merge builder mappings into the trait's exceptionMap
        if (isset($this->app) && !empty($this->app->exceptionMappings)) {
            $this->exceptionMap = array_merge($this->exceptionMap, $this->app->exceptionMappings);
        }

        foreach ($this->exceptionMap as $class => $transformer) {
            if ($t instanceof $class) {
                return $transformer($t);
            }
        }

        return null;
    }

    /**
     * Convert unknown exception to HttpException using best-available mapping.
     */
    protected function transformToHttpException(Throwable $t): HttpException
    {
        // Try custom mappings first
        if ($converted = $this->tryTransformException($t)) {
            return $converted;
        }

        // Fallback: 500 Internal Server Error (do not leak message)
        return HttpExceptionFactory::make(
            status: $t->getCode(),
            uri: null,
            message: 'Internal Server Error',
            previous: $t
        );
    }
}
