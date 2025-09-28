<?php

declare(strict_types=1);

namespace Codefy\Framework\Proxy;

use Codefy\Framework\Application;
use stdClass;

class Codefy extends stdClass
{
    /**
     * Application instance.
     *
     * @var ?Application $php
     */
    public static ?Application $PHP = null;
}
