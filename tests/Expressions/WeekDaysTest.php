<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Expressions\At;
use Codefy\Framework\Scheduler\Expressions\WeekDays;
use PHPUnit\Framework\Assert;

it('should run on weekdays.', function () {
    Assert::assertTrue(WeekDays::make()->isDue(new DateTime('Monday')));
    Assert::assertFalse(WeekDays::make()->isDue(new DateTime('Saturday')));

    Assert::assertTrue(At::make('0 0 * * 1-5')->isDue(new DateTime('Wednesday')));
    Assert::assertFalse(At::make('0 0 * * 1-5')->isDue(new DateTime('Sunday')));
});

it('should run on weekdays with custom input.', function () {
    Assert::assertTrue(WeekDays::make(12, 30)->isDue(new DateTime('Monday 12:30')));
    Assert::assertFalse(WeekDays::make(12, 30)->isDue(new DateTime('Monday 12:31')));

    Assert::assertTrue(At::make('30 12 * * 1-5')->isDue(new DateTime('Wednesday 12:30')));
    Assert::assertFalse(At::make('30 12 * * 1-5')->isDue(new DateTime('Wednesday 12:31')));
});
