<?php

declare(strict_types=1);

namespace Codefy\Framework\Validation;

use Qubus\Inheritance\Contract\ValueType;
use Qubus\Validation\ErrorBag;

interface DataValidator extends ValueType
{
    /**
     * Return all input data as an array.
     *
     * @return array<mixed>
     */
    public function all(): array;

    /**
     * Replace internal data with only the specified keys.
     *
     * @param array<string> $keys
     */
    public function only(array $keys): static;

    /**
     * Replace internal data excluding the specified keys.
     *
     * @param array<string> $keys
     */
    public function except(array $keys): static;

    /**
     * Validate the request data and return validated data.
     *
     * @return array<mixed>
     * @throws \Exception
     */
    public function validated(): array;

    /**
     * Return validated or filtered value.
     *
     * @param mixed $value
     * @param mixed|null $default
     * @return mixed
     * @throws \Exception
     */
    public function value(mixed $value, mixed $default = null): mixed;

    /**
     * Return validation errors.
     */
    public function errors(): ErrorBag;
}
