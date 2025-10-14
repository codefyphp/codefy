<?php

use Codefy\Framework\Application;
use PHPUnit\Framework\Assert;

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
