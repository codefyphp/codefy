<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Processor;

use function is_string;

class Callback extends BaseProcessor implements \Stringable, Processor
{
    public function run(): string|false
    {
        if (
            $this->preventOverlapping &&
            ! $this->mutex->tryLock($this)
        ) {
            return false;
        }

        try {
            $this->callBeforeCallbacks();

            $response = $this->exec($this->command);

            $this->callAfterCallbacks();

            return $response;
        } finally {
            if ($this->preventOverlapping) {
                $this->mutex->unlock($this);
            }
        }
    }

    /**
     * Executes command.
     */
    private function exec(callable $fn): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            $data = $this->call($fn, $this->args);
            $output = ob_get_contents();
            return $output !== '' ? $output : (is_string($data) ? $data : '');
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }

    public function __toString(): string
    {
        return ! empty($this->description)
        ? $this->description
        : 'callback';
    }
}
