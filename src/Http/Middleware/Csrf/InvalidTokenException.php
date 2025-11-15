<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Csrf;

use Qubus\Exception\Http\HttpException;

class InvalidTokenException extends HttpException
{
}
