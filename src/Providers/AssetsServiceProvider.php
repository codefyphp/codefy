<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\Assets;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\public_path;

class AssetsServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws Exception
     */
    public function register(): void
    {
        $config = $this->codefy->configContainer->getConfigKey(key: 'assets');

        // No groups defined. Assume the config is for the default group.
        if (!isset($config['default'])) {
            $this->registerAssetsManagerInstance(name: 'default', config: $config);

            return;
        }

        // Multiple groups
        foreach ($config as $groupName => $groupConfig) {
            $this->registerAssetsManagerInstance($groupName, (array) $groupConfig);
        }
    }

    /**
     * Register an instance of the assets manager library.
     *
     * @param string $name   Name of the group.
     * @param array<array-key, mixed>  $config Config of the group.
     * @return void
     */
    protected function registerAssetsManagerInstance(string $name, array $config): void
    {
        $this->codefy->singleton("assets.group.$name", function () use ($config) {

            if (! isset($config['public_dir'])) {
                $config['public_dir'] = public_path();
            }

            return new Assets($this->codefy, $config);
        });
    }
}
