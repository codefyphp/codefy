<?php

declare(strict_types=1);

namespace Codefy\Foundation\Contracts;

use Qubus\Mail\Mailer;

interface MailerFactory
{
    public static function create(): Mailer;
}
