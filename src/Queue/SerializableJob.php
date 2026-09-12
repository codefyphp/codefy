<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

interface SerializableJob extends ShouldQueue
{
    /** @return array<string, mixed> JSON-compatible job data. */
    public function toPayload(): array;

    /** @param array<string, mixed> $payload Validate and restore job data explicitly. */
    public static function fromPayload(array $payload): static;
}
