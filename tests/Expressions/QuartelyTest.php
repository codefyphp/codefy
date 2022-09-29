<?php

declare(strict_types=1);

use Codefy\Foundation\Scheduler\Expressions\At;
use Codefy\Foundation\Scheduler\Expressions\Quarterly;
use PHPUnit\Framework\Assert;

it('should run quartely', function () {
    // 12:00 AM on the 1st day *after* every 3rd month.
    Assert::assertTrue(Quarterly::make()->isDue(new DateTime('April 01 00:00')));
    Assert::assertFalse(Quarterly::make()->isDue(new DateTime('April 01 00:01')));

    Assert::assertTrue(At::make('0 0 1 */3 *')->isDue(new DateTime('April 01 00:00')));
    Assert::assertFalse(At::make('0 0 1 */3 *')->isDue(new DateTime('April 01 00:01')));

    Assert::assertTrue(Quarterly::make()->isDue(new DateTime('July 01 00:00')));
    Assert::assertFalse(Quarterly::make()->isDue(new DateTime('July 01 00:01')));

    Assert::assertTrue(At::make('0 0 1 */3 *')->isDue(new DateTime('July 01 00:00')));
    Assert::assertFalse(At::make('0 0 1 */3 *')->isDue(new DateTime('July 01 00:01')));

    Assert::assertTrue(Quarterly::make()->isDue(new DateTime('Oct 01 00:00')));
    Assert::assertFalse(Quarterly::make()->isDue(new DateTime('Oct 01 00:01')));

    Assert::assertTrue(At::make('0 0 1 */3 *')->isDue(new DateTime('Oct 01 00:00')));
    Assert::assertFalse(At::make('0 0 1 */3 *')->isDue(new DateTime('Oct 01 00:01')));

    Assert::assertTrue(Quarterly::make()->isDue(new DateTime('Jan 01 00:00')));
    Assert::assertFalse(Quarterly::make()->isDue(new DateTime('Jan 01 00:01')));

    Assert::assertTrue(At::make('0 0 1 */3 *')->isDue(new DateTime('Jan 01 00:00')));
    Assert::assertFalse(At::make('0 0 1 */3 *')->isDue(new DateTime('Jan 01 00:01')));

    // 12:00 AM on the last day *of* each quarter.
    Assert::assertTrue(At::make('0 0 31 3 *')->isDue(new DateTime('March 31 00:00')));
    Assert::assertFalse(At::make('0 0 31 3 *')->isDue(new DateTime('March 31 00:01')));

    Assert::assertTrue(At::make('0 0 30 6 *')->isDue(new DateTime('June 30 00:00')));
    Assert::assertFalse(At::make('0 0 30 6 *')->isDue(new DateTime('June 30 00:01')));

    Assert::assertTrue(At::make('0 0 30 9 *')->isDue(new DateTime('Sep 30 00:00')));
    Assert::assertFalse(At::make('0 0 30 9 *')->isDue(new DateTime('Sep 30 00:01')));

    Assert::assertTrue(At::make('0 0 31 12 *')->isDue(new DateTime('Dec 31 00:00')));
    Assert::assertFalse(At::make('0 0 31 12 *')->isDue(new DateTime('Dec 31 00:01')));

    Assert::assertTrue(At::make('0 0 30 3,6,9,12 *')->isDue(new DateTime('Jun 30 00:00')));
    Assert::assertFalse(At::make('0 0 30 3,6,9,12 *')->isDue(new DateTime('June 30 00:01')));

    Assert::assertTrue(At::make('0 0 30 6,9 *')->isDue(new DateTime('Jun 30 00:00')));
    Assert::assertFalse(At::make('0 0 30 6,9 *')->isDue(new DateTime('Jun 30 00:01')));

    Assert::assertTrue(At::make('0 0 31 3,12 *')->isDue(new DateTime('Dec 31 00:00')));
    Assert::assertFalse(At::make('0 0 31 3,12 *')->isDue(new DateTime('Dec 31 00:01')));
});

it('should run quarterly with custom input.', function () {
    // 4:00 AM on the 15th *after* every 3rd month.
    Assert::assertTrue(Quarterly::make(15, 4)->isDue(new DateTime('April 15 04:00')));
    Assert::assertFalse(Quarterly::make(15, 4)->isDue(new DateTime('April 15 04:01')));

    Assert::assertTrue(At::make('0 4 15 */3 *')->isDue(new DateTime('April 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 */3 *')->isDue(new DateTime('April 15 04:01')));

    Assert::assertTrue(Quarterly::make(15, 4)->isDue(new DateTime('July 15 04:00')));
    Assert::assertFalse(Quarterly::make(15, 4)->isDue(new DateTime('July 15 04:01')));

    Assert::assertTrue(At::make('0 4 15 */3 *')->isDue(new DateTime('July 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 */3 *')->isDue(new DateTime('July 15 04:01')));

    Assert::assertTrue(Quarterly::make(15, 4)->isDue(new DateTime('Oct 15 04:00')));
    Assert::assertFalse(Quarterly::make(15, 4)->isDue(new DateTime('Oct 15 04:01')));

    Assert::assertTrue(At::make('0 4 15 */3 *')->isDue(new DateTime('Oct 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 */3 *')->isDue(new DateTime('Oct 15 04:01')));

    Assert::assertTrue(Quarterly::make(15, 4)->isDue(new DateTime('Jan 15 04:00')));
    Assert::assertFalse(Quarterly::make(15, 4)->isDue(new DateTime('Jan 15 04:01')));

    Assert::assertTrue(At::make('0 4 15 */3 *')->isDue(new DateTime('Jan 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 */3 *')->isDue(new DateTime('Jan 15 04:01')));

    // 4:00 AM on the 15th *of* every 3rd month.
    Assert::assertTrue(At::make('0 4 15 3 *')->isDue(new DateTime('March 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 3 *')->isDue(new DateTime('March 15 04:01')));

    Assert::assertTrue(At::make('0 4 15 6 *')->isDue(new DateTime('June 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 6 *')->isDue(new DateTime('June 15 04:01')));

    Assert::assertTrue(At::make('0 4 15 9 *')->isDue(new DateTime('Sep 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 9 *')->isDue(new DateTime('Sep 15 04:01')));

    Assert::assertTrue(At::make('0 4 15 12 *')->isDue(new DateTime('Dec 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 12 *')->isDue(new DateTime('Dec 15 04:01')));

    Assert::assertTrue(At::make('0 4 15 3,6,9,12 *')->isDue(new DateTime('June 15 04:00')));
    Assert::assertFalse(At::make('0 4 15 3,6,9,12 *')->isDue(new DateTime('June 15 04:01')));
});
