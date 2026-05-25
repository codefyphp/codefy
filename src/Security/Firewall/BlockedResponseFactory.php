<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use Exception;
use Psr\Http\Message\ResponseInterface;

use function Codefy\Framework\Helpers\view;

final readonly class BlockedResponseFactory
{
    /**
     * @throws Exception
     */
    public function create(ThreatMatch $match): ResponseInterface
    {
        return view(template: 'framework::blocked', data: ['type' => $match->type]);
    }
}
