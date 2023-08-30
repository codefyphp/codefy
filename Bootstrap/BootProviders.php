<?php

declare(strict_types=1);

namespace Codefy\Framework\Bootstrap;

use Codefy\Framework\Application;

class BootProviders
{
    public function bootstrap(Application $app): void
    {
        $app->boot();
    }
}
