<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

final readonly class FirewallExclusion
{
    /**
     * @param list<string> $methods
     * @param list<string> $rules
     * @param list<string> $sources
     * @param list<string> $fields
     */
    public function __construct(
        public string $path,
        public array $methods = [],
        public array $rules = [],
        public array $sources = [],
        public array $fields = [],
        public bool $log = true,
    ) {
    }
}
