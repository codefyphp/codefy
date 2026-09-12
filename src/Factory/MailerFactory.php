<?php

declare(strict_types=1);

namespace Codefy\Framework\Factory;

use Codefy\Framework\Contracts\MailerFactory as MailerFactoryContract;
use Codefy\Framework\Support\CodefyMailer;
use Qubus\Mail\Mailer;

use function Codefy\Framework\Helpers\app;

class MailerFactory implements MailerFactoryContract
{
    public static function create(): Mailer
    {
        return new CodefyMailer(config: app(name: 'codefy.config'));
    }
}
