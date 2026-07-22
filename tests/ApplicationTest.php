<?php

use Codefy\Framework\Application;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Http\Response;
use Qubus\Http\ServerRequest;
use Qubus\Mail\Mailer;
use Qubus\Routing\Psr7Router;
use Qubus\Routing\Router;

it(description: 'gets default charset value.', closure: function () {
    $charset = Application::getInstance()->charset;
    Assert::assertEquals(expected: 'UTF-8', actual: $charset);
});

it(description: 'sets charset value.', closure: function () {
    $app = Application::getInstance();
    $app->charset = 'iso-8859-1';
    Assert::assertSame(expected: 'ISO-8859-1', actual: $app->charset);
});

it(description: 'gets default locale value.', closure: function () {
    $locale = Application::getInstance()->locale;
    Assert::assertEquals(expected: 'en', actual: $locale);
});

it(description: 'sets locale value.', closure: function () {
    $app = Application::getInstance();
    $app->locale = 'es-ES';
    Assert::assertSame(expected: 'es-ES', actual: $app->locale);

    $app->withLocale(locale: 'es-ES');
    Assert::assertSame(expected: 'es-ES', actual: $app->locale);
});

it(description: 'gets default controller namespace value.', closure: function () {
    $namespace = Application::getInstance()->controllerNamespace;
    Assert::assertEquals(expected: 'App\\Infrastructure\\Http\\Controllers', actual: $namespace);
});

it(description: 'sets controller namespace value.', closure: function () {
    $app = Application::getInstance();
    $app->controllerNamespace = 'Temp\\App\\Http\\Controllers';
    Assert::assertSame(expected: 'Temp\\App\\Http\\Controllers', actual: $app->controllerNamespace);

    $app->withControllerNamespace(namespace: 'Temp\\App\\Http\\Controllers');
    Assert::assertSame(expected: 'Temp\\App\\Http\\Controllers', actual: $app->controllerNamespace);
});

it(description: 'gets default booted value.', closure: function () {
    $booted = Application::getInstance()->booted;
    Assert::assertEquals(expected: false, actual: $booted);
});

it(description: 'sets booted value.', closure: function () {
    $app = Application::getInstance();
    $app->setBooted(bool: true);
    Assert::assertSame(expected: true, actual: $app->booted);
});

it(description: 'should be the same request instance.', closure: function () {
    $app = Application::getInstance();
    Assert::assertInstanceOf(RequestInterface::class, $app->request);
    Assert::assertInstanceOf(ServerRequest::class, $app->request);
});

it(description: 'should be the same response instance.', closure: function () {
    $app = Application::getInstance();
    Assert::assertInstanceOf(ResponseInterface::class, $app->response);
    Assert::assertInstanceOf(Response::class, $app->response);
});

it(description: 'should be the same mailer instance.', closure: function () {
    $app = Application::getInstance();
    Assert::assertInstanceOf(Mailer::class, $app->mailer);
});

it(description: 'should be the same config instance.', closure: function () {
    $app = Application::getInstance();
    Assert::assertInstanceOf(ConfigContainer::class, $app->configContainer);
});

it(description: 'should be the same router instance.', closure: function () {
    $app = Application::getInstance();
    Assert::assertInstanceOf(Psr7Router::class, $app->router);
    Assert::assertInstanceOf(Router::class, $app->router);
});
