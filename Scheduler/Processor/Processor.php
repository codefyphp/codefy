<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Processor;

use DateTimeZone;

interface Processor
{
    public function mutexName(): string;

    public function maxRuntime(): int;

    public function description(string $description): self;

    public function isDue(string|DateTimeZone|null $timeZone = null): bool;

    public function run(): mixed;
}
