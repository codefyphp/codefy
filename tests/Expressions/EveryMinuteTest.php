<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Expressions\At;
use Codefy\Framework\Scheduler\Expressions\EveryMinute;
use PHPUnit\Framework\Assert;

it('should run every minute.', function () {
    Assert::assertTrue(EveryMinute::make()->isDue(DateTime::createFromFormat('H:i', '00:00')));

    Assert::assertTrue(At::make('* * * * *')->isDue(DateTime::createFromFormat('H:i', '00:00')));
});

it('should run every 5 minutes.', function () {
    Assert::assertTrue(EveryMinute::make(5)->isDue(DateTime::createFromFormat('H:i', '00:05')));

    Assert::assertTrue(At::make('*/5 * * * *')->isDue(DateTime::createFromFormat('H:i', '00:05')));
});

it('should run every nth minute.', function () {
    Assert::assertTrue(EveryMinute::make([5, 20, 45])->isDue(DateTime::createFromFormat('H:i', '00:05')));
    Assert::assertFalse(EveryMinute::make([5, 20, 45])->isDue(DateTime::createFromFormat('H:i', '00:06')));

    Assert::assertTrue(EveryMinute::make([5, 20, 45])->isDue(DateTime::createFromFormat('H:i', '18:45')));
    Assert::assertFalse(EveryMinute::make([5, 20, 45])->isDue(DateTime::createFromFormat('H:i', '18:46')));

    Assert::assertTrue(At::make('5,20,45 * * * *')->isDue(DateTime::createFromFormat('H:i', '00:05')));
    Assert::assertFalse(At::make('5,20,45 * * * *')->isDue(DateTime::createFromFormat('H:i', '00:06')));

    Assert::assertTrue(At::make('5,20,45 * * * *')->isDue(DateTime::createFromFormat('H:i', '18:45')));
    Assert::assertFalse(At::make('5,20,45 * * * *')->isDue(DateTime::createFromFormat('H:i', '18:46')));
});
