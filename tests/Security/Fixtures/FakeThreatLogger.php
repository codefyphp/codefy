<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Security\Fixtures;

use Codefy\Framework\Security\Firewall\ThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatMatch;
use Psr\Http\Message\ServerRequestInterface;

final class FakeThreatLogger implements ThreatLogger
{
    public int $calls = 0;

    public ?ServerRequestInterface $lastRequest = null;

    public ?ThreatMatch $lastMatch = null;

    public function log(
        ServerRequestInterface $request,
        ThreatMatch $match
    ): void {
        $this->calls++;
        $this->lastRequest = $request;
        $this->lastMatch = $match;
    }
}
