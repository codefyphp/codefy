<?php

declare(strict_types=1);

namespace Codefy\Foundation\Contracts;

use Psr\Log\LoggerInterface;

interface LoggerFactory
{
    public static function getLogger(): LoggerInterface;
}
