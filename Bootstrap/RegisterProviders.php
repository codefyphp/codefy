<?php

declare(strict_types=1);

namespace Codefy\Framework\Bootstrap;

use Codefy\Framework\Application;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

class RegisterProviders
{
    /**
     * @throws TypeException
     * @throws Exception
     */
    public function bootstrap(Application $app): void
    {
        $app->registerConfiguredServiceProviders();
    }
}
