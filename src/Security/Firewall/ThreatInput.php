<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

final readonly class ThreatInput
{
    public function __construct(
        public string $source,
        public string $name,
        public string $value,
    ) {
    }
}
