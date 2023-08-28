<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Expressions\Weekly;
use PHPUnit\Framework\Assert;
use Qubus\Exception\Data\TypeException;

it('should run weekly.', function () {
    Assert::assertTrue(Weekly::make()->isDue(new DateTime('Sunday')));
    Assert::assertFalse(Weekly::make()->isDue(new DateTime('Saturday')));

    // Sunday's at 12:00 AM
    Assert::assertTrue(Weekly::make(0)->isDue(new DateTime('Sunday')));
    // Monday's at 12:00 AM
    Assert::assertTrue(Weekly::make(1)->isDue(new DateTime('Monday')));
    // Tuesday's at 12:00 AM
    Assert::assertTrue(Weekly::make(2)->isDue(new DateTime('Tuesday')));
    // Wednesday's at 12:00 AM
    Assert::assertTrue(Weekly::make(3)->isDue(new DateTime('Wednesday')));
    // Thursday's at 12:00 AM
    Assert::assertTrue(Weekly::make(4)->isDue(new DateTime('Thursday')));
    // Friday's at 12:00 AM
    Assert::assertTrue(Weekly::make(5)->isDue(new DateTime('Friday')));
    // Saturday's at 12:00 AM
    Assert::assertTrue(Weekly::make(6)->isDue(new DateTime('Saturday')));
});

it('should run weekly with time.', function () {
    // Sunday's at 5:30 AM
    Assert::assertTrue(Weekly::make(0, 5, 30)->isDue(new DateTime('Sunday 5:30')));
    Assert::assertFalse(Weekly::make(0, 5, 35)->isDue(new DateTime('Sunday 5:30')));

    // Monday's at 5:30 AM
    Assert::assertTrue(Weekly::make(1, 5, 30)->isDue(new DateTime('Monday 5:30')));
    Assert::assertFalse(Weekly::make(1, 5, 30)->isDue(new DateTime('Monday 5:35')));

    // Tuesday's at 5:30 AM
    Assert::assertTrue(Weekly::make(2, 5, 30)->isDue(new DateTime('Tuesday 5:30')));
    Assert::assertFalse(Weekly::make(2, 5, 35)->isDue(new DateTime('Tuesday 5:30')));

    // Wednesday's at 5:30 AM
    Assert::assertTrue(Weekly::make(3, 5, 30)->isDue(new DateTime('Wednesday 5:30')));
    Assert::assertFalse(Weekly::make(3, 5, 30)->isDue(new DateTime('Wednesday 5:35')));

    // Thursday's at 5:30 AM
    Assert::assertTrue(Weekly::make(4, 5, 30)->isDue(new DateTime('Thursday 5:30')));
    Assert::assertFalse(Weekly::make(4, 5, 35)->isDue(new DateTime('Thursday 5:30')));

    // Friday's at 5:30 AM
    Assert::assertTrue(Weekly::make(5, 5, 30)->isDue(new DateTime('Friday 5:30')));
    Assert::assertFalse(Weekly::make(5, 5, 30)->isDue(new DateTime('Friday 5:35')));

    // Saturday's at 5:30 AM
    Assert::assertTrue(Weekly::make(6, 5, 30)->isDue(new DateTime('Saturday 5:30')));
    Assert::assertFalse(Weekly::make(6, 5, 35)->isDue(new DateTime('Saturday 5:30')));
});

it('should run weekly only on weekends.', function () {
    Assert::assertTrue(Weekly::make([0, 6])->isDue(new DateTime('Saturday 00:00')));
    Assert::assertTrue(Weekly::make([0, 6])->isDue(new DateTime('Sunday 00:00')));

    Assert::assertFalse(Weekly::make([0, 6])->isDue(new DateTime('Monday 00:00')));
    Assert::assertFalse(Weekly::make([0, 6])->isDue(new DateTime('Tuesday 00:00')));
    Assert::assertFalse(Weekly::make([0, 6])->isDue(new DateTime('Wednesday 00:00')));
});

it('should through an exception on invalid day input.', function () {
    Weekly::make(10, 5, 30);
})->throws(TypeException::class);

it('should through an exception on invalid hour input.', function () {
    Weekly::make(5, 'invalid', 30);
})->throws(TypeException::class);

it('should through an exception on invalid minute input.', function () {
    Weekly::make(5, 5, 60);
})->throws(TypeException::class);
