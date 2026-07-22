<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Security\Fixtures;

use Codefy\Framework\Security\Firewall\ThreatMatch;
use Codefy\Framework\Security\Firewall\ThreatNotifier;
use Psr\Http\Message\ServerRequestInterface;

final class TestThreatNotifier implements ThreatNotifier
{
    public int $calls = 0;

    public ?ThreatMatch $lastMatch = null;

    public function notify(
        ServerRequestInterface $request,
        ThreatMatch $match
    ): void {
        $this->calls++;
        $this->lastMatch = $match;
    }
}
