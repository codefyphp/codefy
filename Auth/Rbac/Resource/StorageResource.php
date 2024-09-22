<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Resource;

use Codefy\Framework\Auth\Rbac\Guard;

interface StorageResource extends Guard
{
    public function load(): void;
}
