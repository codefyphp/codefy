<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Friday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Monday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Saturday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Sunday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Thursday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Tuesday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Wednesday;
use PHPUnit\Framework\Assert;
use Qubus\Exception\Data\TypeException;

it('should run on specific days of the week.', function () {
    Assert::assertTrue(Sunday::make()->isDue(new DateTime('Sunday')));
    Assert::assertTrue(Monday::make()->isDue(new DateTime('Monday')));
    Assert::assertTrue(Tuesday::make()->isDue(new DateTime('Tuesday')));
    Assert::assertTrue(Wednesday::make()->isDue(new DateTime('Wed')));
    Assert::assertTrue(Thursday::make()->isDue(new DateTime('Thu')));
    Assert::assertTrue(Friday::make()->isDue(new DateTime('Fri')));
    Assert::assertTrue(Saturday::make()->isDue(new DateTime('Sat')));
});

it('should run on specific days of the week with custom input.', function () {
    // Each day at 5:30 AM
    Assert::assertTrue(Sunday::make(5, 30)->isDue(new DateTime('Sunday 05:30')));
    Assert::assertFalse(Sunday::make(5, 35)->isDue(new DateTime('Sunday 05:30')));

    Assert::assertTrue(Monday::make(5, 30)->isDue(new DateTime('Monday 05:30')));
    Assert::assertFalse(Monday::make(5, 30)->isDue(new DateTime('Monday 05:35')));

    Assert::assertTrue(Tuesday::make(5, 30)->isDue(new DateTime('Tuesday 05:30')));
    Assert::assertFalse(Tuesday::make(5, 35)->isDue(new DateTime('Tuesday 05:30')));

    Assert::assertTrue(Wednesday::make(5, 30)->isDue(new DateTime('Wed 05:30')));
    Assert::assertFalse(Wednesday::make(5, 30)->isDue(new DateTime('Wed 05:35')));

    Assert::assertTrue(Thursday::make(5, 30)->isDue(new DateTime('Thu 05:30')));
    Assert::assertFalse(Thursday::make(5, 35)->isDue(new DateTime('Thu 05:30')));

    Assert::assertTrue(Friday::make(5, 30)->isDue(new DateTime('Fri 05:30')));
    Assert::assertFalse(Friday::make(5, 30)->isDue(new DateTime('Fri 05:35')));

    Assert::assertTrue(Saturday::make(5, 30)->isDue(new DateTime('Sat 05:30')));
    Assert::assertFalse(Saturday::make(5, 35)->isDue(new DateTime('Sat 05:30')));
});

it('should throw exception for invalid hour input.', function () {
    Saturday::make('invalid', 30);
})->throws(TypeException::class);

it('should throw exception for invalid minute input.', function () {
    Saturday::make(5, 'invalid');
})->throws(TypeException::class);
