<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Pipes;

class PipeFour
{
    public function __invoke($passable)
    {
        return $passable;
    }
}
