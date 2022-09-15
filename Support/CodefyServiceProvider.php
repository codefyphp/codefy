<?php

declare(strict_types=1);

namespace Codefy\Foundation\Support;

use Codefy\Foundation\Application;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;

abstract class CodefyServiceProvider extends BaseServiceProvider
{
    public function __construct(protected Application $codefy)
    {
        parent::__construct($codefy);
    }
}