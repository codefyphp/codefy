<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Expressions\Date;
use PHPUnit\Framework\Assert;

it('should run at a specific date.', function () {
    $date = '2021-12-01';

    Assert::assertTrue(Date::make($date)->isDue(new DateTime($date)));
    Assert::assertTrue(Date::make(new DateTime($date))->isDue(new DateTime($date)));
    Assert::assertFalse(Date::make($date)->isDue(new DateTime('2021-12-02')));
});

it('should run at a specific date and time.', function () {
    $date = '2021-12-01 12:25';

    Assert::assertTrue(Date::make($date)->isDue(new DateTime($date)));
    Assert::assertTrue(Date::make(new DateTime($date))->isDue(new DateTime($date)));
    Assert::assertFalse(Date::make($date)->isDue(new DateTime('2021-12-02 12:30')));
});
