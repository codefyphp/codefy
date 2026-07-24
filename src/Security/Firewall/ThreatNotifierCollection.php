<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

final readonly class ThreatNotifierCollection
{
    /**
     * @param iterable<ThreatNotifier> $notifiers
     */
    public function __construct(
        private iterable $notifiers
    ) {
    }

    /**
     * @return iterable<ThreatNotifier>
     */
    public function all(): iterable
    {
        return $this->notifiers;
    }
}
