<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Expressions\DayOfWeek;

use Codefy\Framework\Scheduler\Expressions\Expressional;
use Codefy\Framework\Scheduler\Expressions\Weekly;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;

class Thursday implements Expressional
{
    /**
     * Sets the job execution time to run once every Thursday.
     * @throws TypeException
     */
    public static function make(int|string|array $hour = 0, int|string|array $minute = 0): CronExpression
    {
        return Weekly::make(4, $hour, $minute);
    }
}
