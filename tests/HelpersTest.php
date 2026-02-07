<?php

use Codefy\Framework\Scheduler\Schedule;
use PHPUnit\Framework\Assert;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\method_field;

it(description: 'should return a hidden field with DELETE value.', closure: function () {
    $field = method_field(method: 'delete');
    Assert::assertEquals(expected: '<input type="hidden" name="_method" value="DELETE" />', actual: $field);
});

it(description: 'should return an instance of Schedule.', closure: function () {
    $instance = app(Schedule::class);
    Assert::assertInstanceOf(expected: Schedule::class, actual: $instance);
});
