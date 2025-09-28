<?php

declare(strict_types=1);

namespace Codefy\Framework\Bootstrap;

use Codefy\Framework\Application;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;

final class RegisterProviders
{
    protected static array $merge = [];

    /**
     * @throws TypeException
     * @throws Exception
     */
    public function bootstrap(Application $app): void
    {
        $this->mergeAdditionalProviders(app: $app);
        $app->registerConfiguredServiceProviders();
    }

    /**
     * Merge additional configured providers into the configuration.
     *
     * @param Application $app
     * @return void
     * @throws Exception
     */
    protected function mergeAdditionalProviders(Application $app): void
    {
        /** @var ConfigContainer $config */
        $config = $app->make(name: 'codefy.config');

        $arrayMerge = array_unique(
            array: array_merge(
                self::$merge,
                $config->getConfigKey(key: 'app.providers'),
            )
        );

        $config->setConfigKey(key: 'app', value: ['providers' => $arrayMerge,]);
    }

    /**
     * Merge the given providers into the provider configuration
     * before registration.
     *
     * @param array $providers
     * @return void
     */
    public static function merge(array $providers): void
    {
        self::$merge = array_values(
            array: array_filter(
                array: array_unique(
                    array: array_merge(self::$merge, $providers)
                )
            )
        );
    }

    public static function flushState(): void
    {
        self::$merge = [];
    }
}
