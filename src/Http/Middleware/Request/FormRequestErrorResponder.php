<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Request;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function json_encode;

final readonly class FormRequestErrorResponder
{
    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory
    ) {
    }

    /**
     * @return ResponseInterface
     */
    public function unauthorized(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(401)
            ->withBody($this->streamFactory->createStream(json_encode([
                    'message' => 'unauthorized.'
            ])))
            ->withHeader('content-type', 'application/json');
    }

    /**
     * @param array $errors
     * @return ResponseInterface
     */
    public function validationFailed(array $errors): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(422)
            ->withBody($this->streamFactory->createStream(json_encode([
                    'message' => 'validation failed.',
                    'errors' => $errors
            ])))
            ->withHeader('content-type', 'application/json');
    }
}
