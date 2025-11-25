<?php

declare(strict_types=1);

namespace Codefy\Framework\Console;

use Exception;
use Qubus\Config\ConfigContainer;

final class PresetRegistry
{
    public function __construct(protected ConfigContainer $configContainer)
    {
    }

    /**
     * @throws Exception
     */
    public function all(): array
    {
        return $this->configContainer->getConfigKey(key: 'stubs.presets');
    }

    /**
     * @throws Exception
     */
    public function get(string $key): array
    {
        $presets = $this->all();
        return $presets[$key] ?? [];
    }

    /**
     * @throws Exception
     */
    public function stubsPath(): string
    {
        return $this->configContainer->getConfigKey(key: 'stubs.stubs_path');
    }
}
