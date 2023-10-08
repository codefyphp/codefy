<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Exception\Exception;
use Qubus\Mail\Mailer;

final class SmtpMailerServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws Exception
     */
    public function register(): void
    {
        $mailer = (new Mailer())->factory('smtp', $this->codefy->make(name: 'codefy.config'));
        $this->codefy->share(nameOrInstance: $mailer);
    }
}
