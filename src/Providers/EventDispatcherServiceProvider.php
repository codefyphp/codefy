<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Qubus\EventDispatcher\EventDispatcher;
use Qubus\EventDispatcher\Providers\SimpleProvider;
use Qubus\Exception\Exception;

class EventDispatcherServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws Exception
     */
    public function register(): void
    {
        $provider = $this->codefy->configContainer->getConfigKey(
            key: 'app.event_listener',
            default: SimpleProvider::class
        );
        $dispatcher = $this->codefy->configContainer->getConfigKey(
            key: 'app.event_dispatcher',
            default: EventDispatcher::class
        );

        $this->codefy->alias(original: ListenerProviderInterface::class, alias: $provider);
        $this->codefy->alias(original: EventDispatcherInterface::class, alias: $dispatcher);
    }
}
