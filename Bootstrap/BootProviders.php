<?php

declare(strict_types=1);

namespace Codefy\Foundation\Bootstrap;

use Codefy\Foundation\Application;

class BootProviders
{
    public function bootstrap(Application $app): void
    {
        $app->boot();
    }
}
