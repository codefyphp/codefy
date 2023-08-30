<?php

declare(strict_types=1);

namespace Codefy\Framework\Contracts;

use Qubus\Mail\Mailer;

interface MailerFactory
{
    public static function create(): Mailer;
}
