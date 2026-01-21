<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Pipes;

use Closure;

use function str_replace;

final readonly class ReplaceHateWithLove
{
    public function __invoke(string $passable, Closure $next): string
    {
        return $next(
            str_replace(
                search: $this->negativity(),
                replace: 'love',
                subject: $passable,
            )
        );
    }
    private function negativity(): array
    {
        return [
            'hate'
        ];
    }
}
