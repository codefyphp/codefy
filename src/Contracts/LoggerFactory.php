<?php

declare(strict_types=1);

namespace Codefy\Framework\Contracts;

use Psr\Log\LoggerInterface;

interface LoggerFactory
{
    public static function getLogger(): LoggerInterface;
}
