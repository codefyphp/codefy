<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

final readonly class ThreatPattern
{
    public function __construct(
        public string $type,
        public string $severity,
        public float $confidence,
        public string $regex,
    ) {
    }
}
