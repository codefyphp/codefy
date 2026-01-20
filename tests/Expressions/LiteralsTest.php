<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Traits\LiteralAware;
use PHPUnit\Framework\Assert;

$testClass = new class () {
    use LiteralAware;
};

it('should run daily using literal.', function () use ($testClass) {
    Assert::assertTrue(
        $testClass->alias(literal: '@daily')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '00:00'))
    );

    Assert::assertFalse(
        $testClass->alias(literal: '@daily')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '00:01'))
    );
});

it('should run every minute using literal.', function () use ($testClass) {
    Assert::assertTrue(
        $testClass->alias(literal: '@always')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '00:00'))
    );

    Assert::assertTrue(
        $testClass->alias(literal: '@always')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '00:50'))
    );
});

it('should run on weekdays using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias(literal: '@weekdays')->isDue(new DateTime(datetime: 'Monday')));

    Assert::assertFalse($testClass->alias(literal: '@weekdays')->isDue(new DateTime(datetime: 'Saturday')));
});

it('should run on weekends using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias(literal: '@weekends')->isDue(new DateTime(datetime: 'Saturday')));

    Assert::assertFalse($testClass->alias(literal: '@weekends')->isDue(new DateTime(datetime: 'Monday')));
});

it('should run quartely uisng literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias(literal: '@quarterly')->isDue(new DateTime(datetime: 'April 01 00:00')));

    Assert::assertFalse($testClass->alias(literal: '@quarterly')->isDue(new DateTime(datetime: 'March 01 00:00')));
});

it('should run on certain days using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias(literal: '@sunday')->isDue(new DateTime(datetime: 'Sunday')));
    Assert::assertTrue($testClass->alias(literal: '@mon')->isDue(new DateTime(datetime: 'Monday')));
    Assert::assertTrue($testClass->alias(literal: '@tue')->isDue(new DateTime(datetime: 'Tuesday')));

    Assert::assertFalse($testClass->alias(literal: '@sunday')->isDue(new DateTime(datetime: 'Saturday')));
    Assert::assertFalse($testClass->alias(literal: '@mon')->isDue(new DateTime(datetime: 'Sunday')));
    Assert::assertFalse($testClass->alias(literal: '@tues')->isDue(new DateTime(datetime: 'Friday')));
});

it('should run on certain months using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias(literal: '@jan')->isDue(new DateTime(datetime: 'January 01 00:00')));

    Assert::assertFalse($testClass->alias(literal: '@january')->isDue(new DateTime(datetime: 'January 01 00:01')));
    Assert::assertFalse($testClass->alias(literal: '@january')->isDue(new DateTime(datetime: 'December 01 00:00')));

    Assert::assertTrue($testClass->alias(literal: '@feb')->isDue(new DateTime(datetime: 'Feb 01 00:00')));

    Assert::assertFalse($testClass->alias(literal: '@feb')->isDue(new DateTime(datetime: 'Feb 01 00:01')));
    Assert::assertFalse($testClass->alias(literal: '@feb')->isDue(new DateTime(datetime: 'June 01 00:00')));

    Assert::assertTrue($testClass->alias(literal: '@mar')->isDue(new DateTime(datetime: 'March 01 00:00')));

    Assert::assertFalse($testClass->alias(literal: '@mar')->isDue(new DateTime(datetime: 'March 01 00:01')));
    Assert::assertFalse($testClass->alias(literal: '@mar')->isDue(new DateTime(datetime: 'October 01 00:00')));
});

it('should run yearly using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias(literal: '@yearly')->isDue(new DateTime(datetime: 'January 01 00:00')));

    Assert::assertFalse($testClass->alias(literal: '@yearly')->isDue(new DateTime(datetime: 'January 01 00:01')));
    Assert::assertFalse($testClass->alias(literal: '@yearly')->isDue(new DateTime(datetime: 'Feb 01 00:00')));
});

it('should run monthly using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias(literal: '@monthly')->isDue(new DateTime(datetime: 'January 01 00:00')));
    Assert::assertTrue($testClass->alias(literal: '@monthly')->isDue(new DateTime(datetime: 'Feb 01 00:00')));
    Assert::assertTrue($testClass->alias(literal: '@monthly')->isDue(new DateTime(datetime: 'March 01 00:00')));
    Assert::assertTrue($testClass->alias(literal: '@monthly')->isDue(new DateTime(datetime: 'April 01 00:00')));

    Assert::assertFalse($testClass->alias(literal: '@monthly')->isDue(new DateTime(datetime: 'January 01 00:01')));
    Assert::assertFalse($testClass->alias(literal: '@monthly')->isDue(new DateTime(datetime: 'Feb 01 00:01')));
    Assert::assertFalse($testClass->alias(literal: '@monthly')->isDue(new DateTime(datetime: 'March 01 00:01')));
    Assert::assertFalse($testClass->alias(literal: '@monthly')->isDue(new DateTime(datetime: 'April 01 00:01')));
});

it('should run weekly using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias(literal: '@weekly')->isDue(new DateTime(datetime: 'Sunday 00:00')));

    Assert::assertFalse($testClass->alias(literal: '@weekly')->isDue(new DateTime(datetime: 'Sunday 00:01')));
    Assert::assertFalse($testClass->alias(literal: '@weekly')->isDue(new DateTime(datetime: 'Monday 00:00')));
});

it('should run hourly using literal.', function () use ($testClass) {
    Assert::assertTrue(
        $testClass->alias(literal: '@hourly')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '00:00'))
    );
    Assert::assertTrue(
        $testClass->alias(literal: '@hourly')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '01:00'))
    );
    Assert::assertTrue(
        $testClass->alias(literal: '@hourly')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '02:00'))
    );

    Assert::assertFalse(
        $testClass->alias(literal: '@hourly')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '00:01'))
    );
    Assert::assertFalse(
        $testClass->alias(literal: '@hourly')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '01:01'))
    );
    Assert::assertFalse(
        $testClass->alias(literal: '@hourly')->isDue(DateTime::createFromFormat(format: 'H:i', datetime: '02:01'))
    );
});
