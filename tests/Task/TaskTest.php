<?php

use Codefy\Framework\Scheduler\Expressions\At;
use Codefy\Framework\Scheduler\Expressions\EveryMinute;
use Codefy\Framework\Scheduler\Mutex\Locker;
use Codefy\Framework\Scheduler\Processor\Processor as ProcessorContract;
use Codefy\Framework\Scheduler\Schedule;
use Codefy\Framework\Scheduler\Task;
use Codefy\Framework\Tests\Task\FakeTask;
use PHPUnit\Framework\Assert;
use Psr\EventDispatcher\EventDispatcherInterface;
use Qubus\Support\DateTime\QubusDateTimeZone;

it(description: 'returns the instance of itself, Task, and Processor.', closure: function () {
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $cache = Mockery::mock(Locker::class);

    $scheduler = new Schedule($dispatcher, new QubusDateTimeZone('UTC'), $cache);
    $scheduler->task(FakeTask::class);

    Assert::assertInstanceOf(expected: FakeTask::class, actual: $scheduler->task(FakeTask::class));
    Assert::assertInstanceOf(expected: Task::class, actual: $scheduler->task(FakeTask::class));
    Assert::assertInstanceOf(expected: ProcessorContract::class, actual: $scheduler->task(FakeTask::class));

    $time = $scheduler->task(FakeTask::class)->everyMinute();
});

it(description: 'should run every minute.', closure: function () {
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $cache = Mockery::mock(Locker::class);

    $scheduler = new Schedule($dispatcher, new QubusDateTimeZone('UTC'), $cache);

    $time = $scheduler->task(FakeTask::class)->everyMinute();

    Assert::assertEquals(At::make('* * * * *')->isDue(), $time->isDue());
    Assert::assertEquals($scheduler->alias('@always')->isDue(), $time->isDue());
    Assert::assertEquals(At::make('* * * * *')->getExpression(), $time->getExpression());
    Assert::assertEquals($scheduler->alias('@always')->getExpression(), $time->getExpression());
    Assert::assertEquals(EveryMinute::make()->isDue(), $time->isDue());
    Assert::assertEquals(EveryMinute::make()->getExpression(), $time->getExpression());
});
