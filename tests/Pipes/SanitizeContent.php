<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Pipes;

use Closure;

use function strip_tags;

final readonly class SanitizeContent
{
    public function __invoke(string $passable, Closure $next): string
    {
        return $next(strip_tags($passable));
    }
}
