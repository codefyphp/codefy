<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Request\Fixtures;

use Codefy\Framework\Http\Middleware\Request\FormRequestHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class SampleFormHandler extends FormRequestHandler
{
    public function innerHandle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(['message' => 'handled.']);
    }
}
