<?php

declare(strict_types=1);

use Codefy\Framework\Http\Middleware\Request\FormRequestErrorResponder;
use Codefy\Framework\Tests\Request\Fixtures\ExampleFormRequest;
use Codefy\Framework\Tests\Request\Fixtures\SampleFormHandler;
use Codefy\Framework\Tests\Request\Fixtures\UnauthorizeFormRequest;
use Codefy\Framework\Tests\Request\Fixtures\ValidationFormRequest;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Assert;
use Qubus\Http\RequestHandler;
use Qubus\Http\ServerRequestFactory;
use Qubus\Validation\Factories\ValidationFactory;
use Qubus\Validation\ValidationException;

$validationFactory = new ValidationFactory();
$errorResponder = new FormRequestErrorResponder(new ResponseFactory(), new StreamFactory());
$requestFactory = new ServerRequestFactory();

it(
    'returns an unauthorized status when authorization fails',
    function () use ($validationFactory, $requestFactory, $errorResponder) {
        $formRequest = new UnauthorizeFormRequest($validationFactory, $errorResponder);
        $handler = new SampleFormHandler($formRequest);
        $request = $requestFactory->createServerRequest('GET', 'http://example.com/example');

        $response = $handler->handle($request);

        Assert::assertEquals(401, $response->getStatusCode());
        Assert::assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        Assert::assertEquals('{"message":"unauthorized."}', $response->getBody()->__toString());
    }
);

it(
    'returns an unprocessable entity status when validation fails.',
    function () use ($validationFactory, $requestFactory, $errorResponder) {
        $formRequest = new ValidationFormRequest($validationFactory, $errorResponder);
        $handler = new SampleFormHandler($formRequest);
        $request = $requestFactory
            ->createServerRequest('GET', 'http://example.com/example');

        $response = $handler->handle($request);

        Assert::assertEquals(422, $response->getStatusCode());
        Assert::assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        Assert::assertEquals(
            '{"message":"validation failed.","errors":{"id":{"required":"The Id is required"}}}',
            $response->getBody()->__toString()
        );
    }
);

it(
    'returns the original response when validation succeeds.',
    function () use ($validationFactory, $requestFactory, $errorResponder) {
        $formRequest = new ValidationFormRequest($validationFactory, $errorResponder);
        $handler = new SampleFormHandler($formRequest);
        $request = $requestFactory
            ->createServerRequest('GET', 'http://example.com/example');

        $response = $handler->handle($request);

        Assert::assertEquals(422, $response->getStatusCode());
        Assert::assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        Assert::assertEquals(
            '{"message":"validation failed.","errors":{"id":{"required":"The Id is required"}}}',
            $response->getBody()->__toString()
        );
    }
);

it('returns a validated id.', function () use ($requestFactory) {
    $request = $requestFactory
        ->createServerRequest('GET', 'http://example.com/example')
        ->withParsedBody(['id' => '01K9T2ZFJBE3PAPDB0V0K9M6WF']);
    $handler = new RequestHandler(new ResponseFactory());
    $formRequest = new ExampleFormRequest(queryParams: $request->getParsedBody());

    $response = $handler->handle($formRequest);

    Assert::assertEquals(200, $response->getStatusCode());
    Assert::assertEquals(['id' => '01K9T2ZFJBE3PAPDB0V0K9M6WF'], $formRequest->validated());
});

it('throws a ValidationException when the id is null.', function () use ($requestFactory) {
    $request = $requestFactory
        ->createServerRequest('GET', 'http://example.com/example');
    $formRequest = new ExampleFormRequest(parsedBody: $request->getParsedBody());
    $formRequest->validated();
})->throws(ValidationException::class);
