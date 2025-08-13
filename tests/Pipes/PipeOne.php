<?php

declare(strict_types=1);

namespace Codefy\Framework\tests\Pipes;

class PipeOne
{
    public function handle($piped, $next)
    {
        $_SERVER['__test.pipe.one'] = $piped;

        return $next($piped);
    }

    public function differentMethod($piped, $next)
    {
        return $next($piped);
    }
}
