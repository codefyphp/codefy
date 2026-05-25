<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use Psr\Http\Message\ServerRequestInterface;

class NullThreatLogger implements ThreatLogger
{
    public function log(ServerRequestInterface $request, ThreatMatch $match): void
    {
        return;
    }
}
