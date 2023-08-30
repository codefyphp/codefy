<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Expressions\At;
use Codefy\Framework\Scheduler\Expressions\Monthly;
use PHPUnit\Framework\Assert;

it('should run monthly.', function () {
    // The 1st of each month.
    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 January')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 January')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Feb')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Feb')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 March')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 March')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 April')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 April')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 May')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 May')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 June')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 June')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 July')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 July')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Aug')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Aug')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Sept')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Sept')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Oct')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Oct')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Nov')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Nov')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Dec')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Dec')));
    // The 1st of each month.
    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 January')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 January')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Feb')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Feb')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 March')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 March')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 April')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 April')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 May')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 May')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 June')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 June')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 July')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 July')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Aug')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Aug')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Sept')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Sept')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Oct')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Oct')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Nov')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Nov')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Dec')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Dec')));

    // The 1st of each month.
    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 January')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 January')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Feb')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Feb')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 March')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 March')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 April')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 April')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 May')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 May')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 June')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 June')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 July')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 July')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Aug')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Aug')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Sept')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Sept')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Oct')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Oct')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Nov')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Nov')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Dec')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Dec')));
    // The 1st of each month.
    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 January')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 January')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Feb')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Feb')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 March')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 March')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 April')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 April')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 May')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 May')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 June')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 June')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 July')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 July')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Aug')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Aug')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Sept')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Sept')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Oct')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Oct')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Nov')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Nov')));

    Assert::assertTrue(Monthly::make()->isDue(new DateTime('01 Dec')));
    Assert::assertFalse(Monthly::make()->isDue(new DateTime('02 Dec')));
});

it('should run monthly with custom input.', function () {
    // Every 12th of the month at 18:45 PM
    Assert::assertTrue(Monthly::make(1, 12, 18, 45)->isDue(new DateTime('January 12 18:45')));
    Assert::assertFalse(Monthly::make(1, 12, 18, 45)->isDue(new DateTime('January 12 18:46')));

    Assert::assertTrue(Monthly::make(2, 12, 18, 45)->isDue(new DateTime('Feb 12 18:45')));
    Assert::assertFalse(Monthly::make(2, 12, 18, 45)->isDue(new DateTime('Feb 12 18:46')));

    Assert::assertTrue(Monthly::make(3, 12, 18, 45)->isDue(new DateTime('March 12 18:45')));
    Assert::assertFalse(Monthly::make(3, 12, 18, 45)->isDue(new DateTime('March 12 18:46')));

    Assert::assertTrue(Monthly::make(4, 12, 18, 45)->isDue(new DateTime('April 12 18:45')));
    Assert::assertFalse(Monthly::make(4, 12, 18, 45)->isDue(new DateTime('April 12 18:46')));

    Assert::assertTrue(Monthly::make(5, 12, 18, 45)->isDue(new DateTime('May 12 18:45')));
    Assert::assertFalse(Monthly::make(5, 12, 18, 45)->isDue(new DateTime('May 12 18:46')));

    Assert::assertTrue(Monthly::make(6, 12, 18, 45)->isDue(new DateTime('June 12 18:45')));
    Assert::assertFalse(Monthly::make(6, 12, 18, 45)->isDue(new DateTime('June 12 18:46')));

    Assert::assertTrue(Monthly::make(7, 12, 18, 45)->isDue(new DateTime('July 12 18:45')));
    Assert::assertFalse(Monthly::make(7, 12, 18, 45)->isDue(new DateTime('July 12 18:46')));

    Assert::assertTrue(Monthly::make(8, 12, 18, 45)->isDue(new DateTime('Aug 12 18:45')));
    Assert::assertFalse(Monthly::make(8, 12, 18, 45)->isDue(new DateTime('Aug 12 18:46')));

    Assert::assertTrue(Monthly::make(9, 12, 18, 45)->isDue(new DateTime('Sept 12 18:45')));
    Assert::assertFalse(Monthly::make(9, 12, 18, 45)->isDue(new DateTime('Sept 12 18:46')));

    Assert::assertTrue(Monthly::make(10, 12, 18, 45)->isDue(new DateTime('Oct 12 18:45')));
    Assert::assertFalse(Monthly::make(10, 12, 18, 45)->isDue(new DateTime('Oct 12 18:46')));

    Assert::assertTrue(Monthly::make(11, 12, 18, 45)->isDue(new DateTime('Nov 12 18:45')));
    Assert::assertFalse(Monthly::make(11, 12, 18, 45)->isDue(new DateTime('Nov 12 18:46')));

    Assert::assertTrue(Monthly::make(12, 12, 18, 45)->isDue(new DateTime('Dec 12 18:45')));
    Assert::assertFalse(Monthly::make(12, 12, 18, 45)->isDue(new DateTime('Dec 12 18:46')));

    // Run on the 12th of every 3rd and 9th month at 18:45.
    Assert::assertTrue(Monthly::make([3, 9], 12, 18, 45)->isDue(new DateTime('March 12 18:45')));
    Assert::assertTrue(At::make('45 18 12 3,9 *')->isDue(new DateTime('March 12 18:45')));

    Assert::assertTrue(Monthly::make([3, 9], 12, 18, 45)->isDue(new DateTime('Sept 12 18:45')));
    Assert::assertTrue(At::make('45 18 12 3,9 *')->isDue(new DateTime('Sept 12 18:45')));

    Assert::assertFalse(Monthly::make([3, 9], 12, 18, 45)->isDue(new DateTime('July 12 18:45')));
    Assert::assertFalse(At::make('45 18 12 3,9 *')->isDue(new DateTime('July 12 18:45')));

    Assert::assertFalse(Monthly::make([3, 9], 12, 18, 45)->isDue(new DateTime('Oct 12 18:45')));
    Assert::assertFalse(At::make('45 18 12 3,9 *')->isDue(new DateTime('Oct 12 18:45')));

    // Every 20th of the month at 13:25 PM
    $monthly = Monthly::make('*', 20, 13, 25);

    Assert::assertTrue($monthly->isDue(new DateTime('March 20 13:25')));
    Assert::assertTrue($monthly->isDue(new DateTime('June 20 13:25')));
    Assert::assertTrue($monthly->isDue(new DateTime('Oct 20 13:25')));

    Assert::assertFalse($monthly->isDue(new DateTime('Dec 20 13:31')));
    Assert::assertFalse($monthly->isDue(new DateTime('Dec 21 13:31')));
});
