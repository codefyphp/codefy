<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Cache;

use Middlewares\ClearSiteData;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

final class ClearSiteDataMiddleware extends ClearSiteData
{
    /**
     * @throws Exception
     */
    public function __construct(protected(set) ConfigContainer $configContainer)
    {
        parent::__construct($this->configContainer->getConfigKey(key: 'http-cache.types'));
    }
}
