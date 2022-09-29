<?php

declare(strict_types=1);

use Codefy\Foundation\Scheduler\Expressions\At;
use Codefy\Foundation\Scheduler\Expressions\Hourly;
use PHPUnit\Framework\Assert;

it('should run hourly.', function () {
    Assert::assertTrue(Hourly::make()->isDue(DateTime::createFromFormat('H:i', '11:00')));
    Assert::assertFalse(Hourly::make()->isDue(DateTime::createFromFormat('H:i', '11:01')));

    Assert::assertTrue(At::make('0 * * * *')->isDue(DateTime::createFromFormat('H:i', '11:00')));
    Assert::assertFalse(At::make('0 * * * *')->isDue(DateTime::createFromFormat('H:i', '11:01')));
});

it('should run hourly with custom input.', function () {
    Assert::assertTrue(Hourly::make(19)->isDue(DateTime::createFromFormat('H:i', '11:19')));
    Assert::assertFalse(Hourly::make(19)->isDue(DateTime::createFromFormat('H:i', '11:20')));

    Assert::assertTrue(At::make('19 * * * *')->isDue(DateTime::createFromFormat('H:i', '11:19')));
    Assert::assertFalse(At::make('19 * * * *')->isDue(DateTime::createFromFormat('H:i', '11:20')));
});
