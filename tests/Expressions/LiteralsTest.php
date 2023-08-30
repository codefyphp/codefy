<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Traits\LiteralAware;
use PHPUnit\Framework\Assert;

$testClass = new class() {
    use LiteralAware;

};

it('should run daily using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@daily')->isDue(DateTime::createFromFormat('H:i', '00:00')));

    Assert::assertFalse($testClass->alias('@daily')->isDue(DateTime::createFromFormat('H:i', '00:01')));
});

it('should run every minute using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@always')->isDue(DateTime::createFromFormat('H:i', '00:00')));

    Assert::assertTrue($testClass->alias('@always')->isDue(DateTime::createFromFormat('H:i', '00:50')));
});

it('should run on weekdays using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@weekdays')->isDue(new DateTime('Monday')));

    Assert::assertFalse($testClass->alias('@weekdays')->isDue(new DateTime('Saturday')));
});

it('should run on weekends using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@weekends')->isDue(new DateTime('Saturday')));

    Assert::assertFalse($testClass->alias('@weekends')->isDue(new DateTime('Monday')));
});

it('should run quartely uisng literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@quarterly')->isDue(new DateTime('April 01 00:00')));

    Assert::assertFalse($testClass->alias('@quarterly')->isDue(new DateTime('March 01 00:00')));
});

it('should run on certain days using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@sunday')->isDue(new DateTime('Sunday')));
    Assert::assertTrue($testClass->alias('@mon')->isDue(new DateTime('Monday')));
    Assert::assertTrue($testClass->alias('@tue')->isDue(new DateTime('Tuesday')));

    Assert::assertFalse($testClass->alias('@sunday')->isDue(new DateTime('Saturday')));
    Assert::assertFalse($testClass->alias('@mon')->isDue(new DateTime('Sunday')));
    Assert::assertFalse($testClass->alias('@tues')->isDue(new DateTime('Friday')));
});

it('should run on certain months using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@jan')->isDue(new DateTime('January 01 00:00')));

    Assert::assertFalse($testClass->alias('@january')->isDue(new DateTime('January 01 00:01')));
    Assert::assertFalse($testClass->alias('@january')->isDue(new DateTime('December 01 00:00')));

    Assert::assertTrue($testClass->alias('@feb')->isDue(new DateTime('Feb 01 00:00')));

    Assert::assertFalse($testClass->alias('@feb')->isDue(new DateTime('Feb 01 00:01')));
    Assert::assertFalse($testClass->alias('@feb')->isDue(new DateTime('June 01 00:00')));

    Assert::assertTrue($testClass->alias('@mar')->isDue(new DateTime('March 01 00:00')));

    Assert::assertFalse($testClass->alias('@mar')->isDue(new DateTime('March 01 00:01')));
    Assert::assertFalse($testClass->alias('@mar')->isDue(new DateTime('October 01 00:00')));
});

it('should run yearly using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@yearly')->isDue(new DateTime('January 01 00:00')));

    Assert::assertFalse($testClass->alias('@yearly')->isDue(new DateTime('January 01 00:01')));
    Assert::assertFalse($testClass->alias('@yearly')->isDue(new DateTime('Feb 01 00:00')));
});

it('should run monthly using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@monthly')->isDue(new DateTime('January 01 00:00')));
    Assert::assertTrue($testClass->alias('@monthly')->isDue(new DateTime('Feb 01 00:00')));
    Assert::assertTrue($testClass->alias('@monthly')->isDue(new DateTime('March 01 00:00')));
    Assert::assertTrue($testClass->alias('@monthly')->isDue(new DateTime('April 01 00:00')));

    Assert::assertFalse($testClass->alias('@monthly')->isDue(new DateTime('January 01 00:01')));
    Assert::assertFalse($testClass->alias('@monthly')->isDue(new DateTime('Feb 01 00:01')));
    Assert::assertFalse($testClass->alias('@monthly')->isDue(new DateTime('March 01 00:01')));
    Assert::assertFalse($testClass->alias('@monthly')->isDue(new DateTime('April 01 00:01')));
});

it('should run weekly using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@weekly')->isDue(new DateTime('Sunday 00:00')));

    Assert::assertFalse($testClass->alias('@weekly')->isDue(new DateTime('Sunday 00:01')));
    Assert::assertFalse($testClass->alias('@weekly')->isDue(new DateTime('Monday 00:00')));
});

it('should run hourly using literal.', function () use ($testClass) {
    Assert::assertTrue($testClass->alias('@hourly')->isDue(DateTime::createFromFormat('H:i', '00:00')));
    Assert::assertTrue($testClass->alias('@hourly')->isDue(DateTime::createFromFormat('H:i', '01:00')));
    Assert::assertTrue($testClass->alias('@hourly')->isDue(DateTime::createFromFormat('H:i', '02:00')));

    Assert::assertFalse($testClass->alias('@hourly')->isDue(DateTime::createFromFormat('H:i', '00:01')));
    Assert::assertFalse($testClass->alias('@hourly')->isDue(DateTime::createFromFormat('H:i', '01:01')));
    Assert::assertFalse($testClass->alias('@hourly')->isDue(DateTime::createFromFormat('H:i', '02:01')));
});
