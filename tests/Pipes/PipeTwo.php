<?php

declare(strict_types=1);

namespace Codefy\Framework\tests\Pipes;

class PipeTwo
{
    public function handle($piped, $next, $parameter1 = null, $parameter2 = null)
    {
        $_SERVER['__test.pipe.parameters'] = [$parameter1, $parameter2];

        return $next($piped);
    }
}
