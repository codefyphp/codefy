<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use Psr\Http\Message\ServerRequestInterface;

use function Codefy\Framework\Helpers\logger;

class InfoThreatLogger implements ThreatLogger
{
    public function log(ServerRequestInterface $request, ThreatMatch $match): void
    {
        logger(
            level: 'info',
            message: $match->excluded
                ? 'Firewall rule excluded'
                : 'Firewall detected',
            context: [
                'group' => $match->group,
                'type' => $match->type,
                'severity' => $match->severity,
                'source' => $match->source,
                'field' => $match->field,
                'value' => $match->value,
            ]
        );
    }
}
