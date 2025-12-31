<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Request;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Validation\ErrorBag;

interface DataTransformerRequest extends ServerRequestInterface
{
    /**
     * Return all input data as an array.
     */
    public function all(): array;

    /**
     * Replace internal data with only the specified keys.
     */
    public function only(array $keys): static;

    /**
     * Replace internal data excluding the specified keys.
     */
    public function except(array $keys): static;

    /**
     * Validate the request data and return validated data.
     *
     * @throws Exception
     */
    public function validated(): array;

    /**
     * Return validated or filtered value.
     *
     * @param mixed $value
     * @param mixed|null $default
     * @return mixed
     * @throws Exception
     */
    public function value(mixed $value, mixed $default = null): mixed;

    /**
     * Return validation errors.
     */
    public function errors(): ErrorBag;
}
