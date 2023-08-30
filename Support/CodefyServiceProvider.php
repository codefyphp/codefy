<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Application;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;

abstract class CodefyServiceProvider extends BaseServiceProvider
{
    public function __construct(protected Application $codefy)
    {
        parent::__construct($codefy);
    }
}