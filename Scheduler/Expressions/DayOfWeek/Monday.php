<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Expressions\DayOfWeek;

use Codefy\Framework\Scheduler\Expressions\Expressional;
use Codefy\Framework\Scheduler\Expressions\Weekly;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;

class Monday implements Expressional
{
    /**
     * Sets the job execution time to run once every Monday.
     * @throws TypeException
     */
    public static function make(int|string|array $hour = 0, int|string|array $minute = 0): CronExpression
    {
        return Weekly::make(1, $hour, $minute);
    }
}
