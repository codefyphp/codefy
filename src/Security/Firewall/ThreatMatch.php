<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

final readonly class ThreatMatch
{
    public function __construct(
        public string $type,
        public string $severity,
        public float $confidence,
        public string $pattern,
        public string $value,
        public ?string $group = null,
        public ?string $source = null,
        public ?string $field = null,
        public bool $excluded = false,
    ) {
    }
}
