<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Cache;

use Middlewares\Expires;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

final class CacheExpiresMiddleware extends Expires
{
    /**
     * @throws Exception
     */
    public function __construct(protected(set) ConfigContainer $configContainer)
    {
        $this->defaultExpires($this->configContainer->getConfigKey(key: 'http-cache.default'));
        parent::__construct($this->configContainer->getConfigKey(key: 'http-cache.expires'));
    }
}
