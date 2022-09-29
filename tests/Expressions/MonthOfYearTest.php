<?php

declare(strict_types=1);

use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\April;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\August;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\December;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\February;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\January;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\July;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\June;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\March;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\May;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\November;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\October;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\September;
use PHPUnit\Framework\Assert;

it('should monthly with custom month.', function () {
    // The 1st of each month.
    Assert::assertTrue(January::make()->isDue(new DateTime('01 January')));
    Assert::assertFalse(January::make()->isDue(new DateTime('02 January')));

    Assert::assertTrue(February::make()->isDue(new DateTime('01 Feb')));
    Assert::assertFalse(February::make()->isDue(new DateTime('02 Feb')));

    Assert::assertTrue(March::make()->isDue(new DateTime('01 March')));
    Assert::assertFalse(March::make()->isDue(new DateTime('02 March')));

    Assert::assertTrue(April::make()->isDue(new DateTime('01 April')));
    Assert::assertFalse(April::make()->isDue(new DateTime('02 April')));

    Assert::assertTrue(May::make()->isDue(new DateTime('01 May')));
    Assert::assertFalse(May::make()->isDue(new DateTime('02 May')));

    Assert::assertTrue(June::make()->isDue(new DateTime('01 June')));
    Assert::assertFalse(June::make()->isDue(new DateTime('02 June')));

    Assert::assertTrue(July::make()->isDue(new DateTime('01 July')));
    Assert::assertFalse(July::make()->isDue(new DateTime('02 July')));

    Assert::assertTrue(August::make()->isDue(new DateTime('01 Aug')));
    Assert::assertFalse(August::make()->isDue(new DateTime('02 Aug')));

    Assert::assertTrue(September::make()->isDue(new DateTime('01 Sept')));
    Assert::assertFalse(September::make()->isDue(new DateTime('02 Sept')));

    Assert::assertTrue(October::make()->isDue(new DateTime('01 Oct')));
    Assert::assertFalse(October::make()->isDue(new DateTime('02 Oct')));

    Assert::assertTrue(November::make()->isDue(new DateTime('01 Nov')));
    Assert::assertFalse(November::make()->isDue(new DateTime('02 Nov')));

    Assert::assertTrue(December::make()->isDue(new DateTime('01 Dec')));
    Assert::assertFalse(December::make()->isDue(new DateTime('02 Dec')));
});

it('should run with custom month and input.', function () {
    // The 15th of each month at 10:00 AM.
    Assert::assertTrue(January::make(15, 10, 0)->isDue(new DateTime('January 15 10:00')));
    Assert::assertFalse(January::make(16, 10, 0)->isDue(new DateTime('January 15 10:00')));

    Assert::assertTrue(February::make(15, 10, 0)->isDue(new DateTime('Feb 15 10:00')));
    Assert::assertFalse(February::make(15, 10, 0)->isDue(new DateTime('Feb 16 10:00')));

    Assert::assertTrue(March::make(15, 10, 0)->isDue(new DateTime('March 15 10:00')));
    Assert::assertFalse(March::make(15, 11, 0)->isDue(new DateTime('March 15 10:00')));

    Assert::assertTrue(April::make(15, 10, 0)->isDue(new DateTime('April 15 10:00')));
    Assert::assertFalse(April::make(15, 10, 0)->isDue(new DateTime('April 15 11:00')));

    Assert::assertTrue(May::make(15, 10, 0)->isDue(new DateTime('May 15 10:00')));
    Assert::assertFalse(May::make(15, 10, 30)->isDue(new DateTime('May 15 10:00')));

    Assert::assertTrue(June::make(15, 10, 0)->isDue(new DateTime('June 15 10:00')));
    Assert::assertFalse(June::make(15, 10, 0)->isDue(new DateTime('June 15 10:30')));

    Assert::assertTrue(July::make(15, 10, 0)->isDue(new DateTime('July 15 10:00')));
    Assert::assertFalse(July::make(15, 12, 0)->isDue(new DateTime('July 15 10:00')));

    Assert::assertTrue(August::make(15, 10, 0)->isDue(new DateTime('Aug 15 10:00')));
    Assert::assertFalse(August::make(15, 10, 0)->isDue(new DateTime('Aug 15 12:00')));

    Assert::assertTrue(September::make(15, 10, 0)->isDue(new DateTime('Sept 15 10:00')));
    Assert::assertFalse(September::make(15, 10, 45)->isDue(new DateTime('Sept 15 10:00')));

    Assert::assertTrue(October::make(15, 10, 0)->isDue(new DateTime('Oct 15 10:00')));
    Assert::assertFalse(October::make(15, 10, 0)->isDue(new DateTime('Oct 15 10:45')));

    Assert::assertTrue(November::make(15, 10, 0)->isDue(new DateTime('Nov 15 10:00')));
    Assert::assertFalse(November::make(18, 10, 0)->isDue(new DateTime('Nov 15 10:00')));

    Assert::assertTrue(December::make(15, 10, 0)->isDue(new DateTime('Dec 15 10:00')));
    Assert::assertFalse(December::make(15, 10, 0)->isDue(new DateTime('Dec 18 10:00')));
});
