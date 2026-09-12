<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Mutex;

use Codefy\Framework\Scheduler\Processor\Processor;

/** Atomic process locks on a single host. Lock files must never be unlinked while workers run. */
final class FileLocker implements Locker
{
    /** @var array<string, resource> */
    private array $locks = [];

    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the scheduler lock directory.');
        }
    }

    public function tryLock(Processor $processor): bool
    {
        $name = $processor->mutexName();
        if (isset($this->locks[$name])) {
            return false;
        }
        $handle = $this->open($name);
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }
        $this->locks[$name] = $handle;
        return true;
    }

    public function hasLock(Processor $processor): bool
    {
        $name = $processor->mutexName();
        if (isset($this->locks[$name])) {
            return true;
        }
        $handle = $this->open($name);
        $available = flock($handle, LOCK_EX | LOCK_NB);
        if ($available) {
            flock($handle, LOCK_UN);
        }
        fclose($handle);
        return !$available;
    }

    public function unlock(Processor $processor): bool
    {
        $name = $processor->mutexName();
        if (!isset($this->locks[$name])) {
            return false;
        }
        $handle = $this->locks[$name];
        unset($this->locks[$name]);
        $released = flock($handle, LOCK_UN);
        fclose($handle);
        return $released;
    }

    /** @return resource */
    private function open(string $name)
    {
        $handle = fopen($this->directory . DIRECTORY_SEPARATOR . hash('sha256', $name) . '.lock', 'c');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open the scheduler lock file.');
        }
        return $handle;
    }

    public function __destruct()
    {
        foreach ($this->locks as $handle) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
