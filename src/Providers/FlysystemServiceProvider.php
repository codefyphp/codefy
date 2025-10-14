<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\FileSystem\Adapter\LocalFlysystemAdapter;
use Qubus\FileSystem\FileSystem;

final class FlysystemServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->registerFilesystem();
    }

    private function registerFilesystem(): void
    {
        $this->registerAdapter();

        $this->codefy->delegate(
            name: 'filesystem.default',
            callableOrMethodStr: fn () => new FileSystem($this->codefy->make(name: 'flysystem.adapter'))
        );
    }

    private function registerAdapter(): void
    {
        $this->codefy->delegate(
            name: 'flysystem.adapter',
            callableOrMethodStr: fn () => new LocalFlysystemAdapter($this->codefy->make(name: 'codefy.config'))
        );

        $this->codefy->share(nameOrInstance: 'flysystem.adapter');
    }
}
