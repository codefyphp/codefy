<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Errors;

use Qubus\Error\Error;

class HttpRequestError extends Error
{
    /**
     * @param string $message
     * @param int|string $code
     * @param array<array-key, mixed> $context
     */
    public function __construct(string $message = '', int|string $code = '', $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}
