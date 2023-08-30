<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Processor;

use Stringable;

use function is_string;

class Callback extends BaseProcessor implements Stringable, Processor
{
    public function run(): mixed
    {
        if ($this->preventOverlapping &&
            ! $this->mutex->tryLock($this)
        ) {
            return false;
        }

        $this->callBeforeCallbacks();

        $response = $this->exec($this->command);

        $this->callAfterCallbacks();

        return $response;
    }

    /**
     * Executes command.
     */
    private function exec(callable $fn): mixed
    {
        $data = $this->call($fn, $this->args, true);

        return is_string($data) ? $data : '';
    }

    public function __toString(): string
    {
        return ! empty($this->description)
        ? $this->description
        : 'callback';
    }
}
