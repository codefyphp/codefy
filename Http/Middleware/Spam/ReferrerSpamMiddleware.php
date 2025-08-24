<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Spam;

use Middlewares\ReferrerSpam;
use Psr\Http\Message\ResponseFactoryInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

final class ReferrerSpamMiddleware extends ReferrerSpam
{
    /**
     * @throws Exception
     */
    public function __construct(
        protected(set) ConfigContainer $configContainer,
        ?ResponseFactoryInterface $responseFactory = null
    ) {
        parent::__construct($this->configContainer->getConfigKey(key: 'referrer-spam.blacklist'), $responseFactory);
    }
}
