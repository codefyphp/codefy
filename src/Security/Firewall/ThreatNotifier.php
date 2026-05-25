<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use Psr\Http\Message\ServerRequestInterface;

interface ThreatNotifier
{
    public function notify(ServerRequestInterface $request, ThreatMatch $match): void;
}
