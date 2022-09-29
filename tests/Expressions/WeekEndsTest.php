<?php

declare(strict_types=1);

use Codefy\Foundation\Scheduler\Expressions\At;
use Codefy\Foundation\Scheduler\Expressions\WeekEnds;
use PHPUnit\Framework\Assert;

it('should run on weekends.', function () {
    Assert::assertTrue(WeekEnds::make()->isDue(new DateTime('Saturday')));
    Assert::assertFalse(WeekEnds::make()->isDue(new DateTime('Monday')));

    Assert::assertTrue(At::make('0 0 * * 0,6')->isDue(new DateTime('Sunday')));
    Assert::assertFalse(At::make('0 0 * * 0,6')->isDue(new DateTime('Wednesday')));
});

it('should run on weekends with custom input.', function () {
    Assert::assertTrue(WeekEnds::make(12, 30)->isDue(new DateTime('Saturday 12:30')));
    Assert::assertFalse(WeekEnds::make(12, 30)->isDue(new DateTime('Saturday 12:31')));

    Assert::assertTrue(At::make('30 12 * * 0,6')->isDue(new DateTime('Sunday 12:30')));
    Assert::assertFalse(At::make('30 12 * * 0,6')->isDue(new DateTime('Sunday 12:31')));
});
