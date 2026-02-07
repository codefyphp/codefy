<?php

declare(strict_types=1);

namespace Codefy\Framework\Console;

use Qubus\Config\ConfigContainer;

final class PresetRegistry
{
    public function __construct(protected ConfigContainer $configContainer)
    {
    }

    /**
     * @return array<array-key, array>
     * @throws \Exception
     */
    public function all(): array
    {
        return $this->configContainer->getConfigKey(key: 'stubs.presets');
    }

    /**
     * @return array<array-key, array>
     * @throws \Exception
     */
    public function get(string $key): array
    {
        $presets = $this->all();
        return $presets[$key] ?? [];
    }

    /**
     * @throws \Exception
     */
    public function stubsPath(): string
    {
        return $this->configContainer->getConfigKey(key: 'stubs.stubs_path');
    }
}
