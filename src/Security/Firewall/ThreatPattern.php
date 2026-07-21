<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use function in_array;

final readonly class ThreatPattern
{
    /**
     * @param list<string> $allowedSources
     */
    public function __construct(
        public string $group,
        public string $type,
        public string $severity,
        public float $confidence,
        public string $regex,
        public array $allowedSources = [],
        public int $priority = 0,
    ) {
    }

    public function supports(ThreatInput $input): bool
    {
        return $this->allowedSources === [] || in_array($input->source, $this->allowedSources, true);
    }
}
