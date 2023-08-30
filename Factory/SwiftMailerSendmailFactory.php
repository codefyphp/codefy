<?php

declare(strict_types=1);

namespace Codefy\Framework\Factory;

use Codefy\Framework\Contracts\MailerFactory;
use Qubus\Exception\Exception;
use Qubus\Mail\Mailer;

use function Codefy\Framework\Helpers\app;

class SwiftMailerSendmailFactory implements MailerFactory
{
    /**
     * @throws Exception
     */
    public static function create(): Mailer
    {
        return (new Mailer())->factory(driver: 'sendmail', config: app(name: 'codefy.config'));
    }
}
