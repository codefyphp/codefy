<?php

declare(strict_types=1);

namespace Codefy\Framework\Bootstrap;

use Codefy\Framework\Application;
use Qubus\Exception\Data\TypeException;

class RegisterProviders
{
    /**
     * @throws TypeException
     */
    public function bootstrap(Application $app): void
    {
        $app->registerConfiguredServiceProviders();
    }
}
