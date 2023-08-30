<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Expressions\At;
use Codefy\Framework\Scheduler\Expressions\Daily;
use PHPUnit\Framework\Assert;

it('should run daily.', function () {
    Assert::assertTrue(Daily::make()->isDue(DateTime::createFromFormat('H:i', '00:00')));

    Assert::assertTrue(At::make('0 0 * * *')->isDue(DateTime::createFromFormat('H:i', '00:00')));
});

it('should run with custom input.', function () {
    Assert::assertTrue(Daily::make(19, 53)->isDue(DateTime::createFromFormat('H:i', '19:53')));
    Assert::assertFalse(Daily::make(19, 53)->isDue(DateTime::createFromFormat('H:i', '18:53')));

    Assert::assertTrue(At::make('53 19 * * *')->isDue(DateTime::createFromFormat('H:i', '19:53')));
    Assert::assertFalse(At::make('53 19 * * *')->isDue(DateTime::createFromFormat('H:i', '18:53')));

    // Run daily at 12:00 AM and 12:00 PM
    Assert::assertTrue(Daily::make([0,12])->isDue(DateTime::createFromFormat('H:i', '00:00')));
    Assert::assertFalse(Daily::make([0,12])->isDue(DateTime::createFromFormat('H:i', '00:01')));

    Assert::assertTrue(Daily::make([0,12])->isDue(DateTime::createFromFormat('H:i', '12:00')));
    Assert::assertFalse(Daily::make([0,12])->isDue(DateTime::createFromFormat('H:i', '12:01')));

    // Run daily at 12:30 AM and 12:30 PM
    Assert::assertTrue(Daily::make([0,12], 30)->isDue(DateTime::createFromFormat('H:i', '00:30')));
    Assert::assertFalse(Daily::make([0,12], 30)->isDue(DateTime::createFromFormat('H:i', '00:31')));

    Assert::assertTrue(Daily::make([0,12], 30)->isDue(DateTime::createFromFormat('H:i', '12:30')));
    Assert::assertFalse(Daily::make([0,12], 30)->isDue(DateTime::createFromFormat('H:i', '12:31')));

    // Run daily at 12:00 & 12:30 AM and 12:00 & 12:30 PM
    Assert::assertTrue(Daily::make([0,12], [0, 30])->isDue(DateTime::createFromFormat('H:i', '00:00')));
    Assert::assertFalse(Daily::make([0,12], [0, 30])->isDue(DateTime::createFromFormat('H:i', '00:01')));

    Assert::assertTrue(Daily::make([0,12], [0, 30])->isDue(DateTime::createFromFormat('H:i', '00:30')));
    Assert::assertFalse(Daily::make([0,12], [0, 30])->isDue(DateTime::createFromFormat('H:i', '00:31')));

    Assert::assertTrue(Daily::make([0,12], [0,30])->isDue(DateTime::createFromFormat('H:i', '12:00')));
    Assert::assertFalse(Daily::make([0,12], [0,30])->isDue(DateTime::createFromFormat('H:i', '12:01')));

    Assert::assertTrue(Daily::make([0,12], [0,30])->isDue(DateTime::createFromFormat('H:i', '12:30')));
    Assert::assertFalse(Daily::make([0,12], [0,30])->isDue(DateTime::createFromFormat('H:i', '12:31')));
});
