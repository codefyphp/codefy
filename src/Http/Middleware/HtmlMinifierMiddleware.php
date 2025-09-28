<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Middlewares\Minifier;
use Psr\Http\Server\MiddlewareInterface;
use WyriHaximus\HtmlCompress\Factory;

class HtmlMinifierMiddleware extends Minifier implements MiddlewareInterface
{
    public function __construct()
    {
        parent::__construct(Factory::construct(), 'text/html');
    }
}
