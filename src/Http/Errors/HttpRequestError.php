<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Errors;

use Qubus\Error\Error;

class HttpRequestError extends Error
{
    public function __construct(string $message = '', int|string $code = '', $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}
