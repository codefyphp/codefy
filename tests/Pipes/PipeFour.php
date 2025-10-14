<?php

declare(strict_types=1);

namespace Codefy\Framework\tests\Pipes;

class PipeFour
{
    public function __invoke($passable)
    {
        return $passable;
    }
}
