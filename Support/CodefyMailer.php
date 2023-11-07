<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Application;
use Qubus\Mail\QubusMailer;

final class CodefyMailer extends QubusMailer
{
    public const VERSION = Application::APP_VERSION;
}
