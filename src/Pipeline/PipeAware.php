<?php

declare(strict_types=1);

namespace Codefy\Framework\Pipeline;

use Codefy\Framework\Proxy\Codefy;

// @phpstan-ignore trait.unused
trait PipeAware
{
    public function pipeThrough(callable $pipes, bool $withTransaction = false): Chainable
    {
        $pipeline = Codefy::$PHP->pipeline->build();
        if ($withTransaction) {
            $pipeline->withTransaction();
        }
        return $pipeline->send($this)->through($pipes);
    }
}
