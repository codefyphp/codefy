<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Queue\Fixtures;

use Codefy\Framework\Queue\SerializableJob;
use Codefy\Framework\Queue\SimpleQueue;

class ReleaseTestJob extends SimpleQueue implements SerializableJob
{
    public static array $handled = [];
    public function __construct(public string $value = 'test', public bool $succeeds = true)
    {
        $this->name = 'test';
        $this->executions = 2;
    }
    public function handle(): bool
    {
        self::$handled[] = $this->value;
        if ($this->value === 'throw') { throw new \TypeError('Job failed'); }
        return $this->succeeds;
    }
    public function toPayload(): array { return ['value' => $this->value, 'succeeds' => $this->succeeds]; }
    public static function fromPayload(array $payload): static { return new static($payload['value'], $payload['succeeds']); }
}

