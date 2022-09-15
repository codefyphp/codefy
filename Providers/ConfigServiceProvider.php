<?php

declare(strict_types=1);

namespace Codefy\Foundation\Providers;

use Codefy\Foundation\Support\CodefyServiceProvider;
use Qubus\Config\Collection;
use Qubus\Config\Configuration;

use function Codefy\Foundation\Helpers\env;

final class ConfigServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->defineParam(
            paramName: 'config',
            value: new Configuration(
                [
                    'path' => $this->codefy->configPath(),
                    'dotenv' => $this->codefy->basePath(),
                    'environment' => env(key: 'APP_ENV', default: 'local'),
                ]
            )
        );

        $this->codefy->share(nameOrInstance: Configuration::class);
        $this->codefy->alias(original: 'codefy.config', alias: Collection::class);
        $this->codefy->share(nameOrInstance: 'codefy.config');
    }
}
