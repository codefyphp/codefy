<?php

declare(strict_types=1);

namespace Codefy\Framework\tests\Pipes;

use Exception;

class PipeThree
{
    /**
     * @throws Exception
     */
    public function handle($piped, $next)
    {
        throw new Exception('Foo');
    }
}
