<?php

declare(strict_types=1);

namespace Codefy\Framework\Factory;

use Codefy\Framework\Contracts\MailerFactory;
use Codefy\Framework\Support\CodefyMailer;
use Qubus\Mail\Mailer;

use function Codefy\Framework\Helpers\app;

class PHPMailerSmtpFactory implements MailerFactory
{
    public static function create(): Mailer
    {
        return new CodefyMailer(config: app(name: 'codefy.config'));
    }
}
