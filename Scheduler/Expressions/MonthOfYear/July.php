<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Expressions\MonthOfYear;

use Codefy\Framework\Scheduler\Expressions\Expressional;
use Codefy\Framework\Scheduler\Expressions\Monthly;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;

final class July implements Expressional
{
    /**
     * Sets the job execution time to run once every July.
     * @throws TypeException
     */
    public static function make(
        int|string|array $day = 1,
        int|string|array $hour = 0,
        int|string|array $minute = 0
    ): CronExpression {
        return Monthly::make(7, $day, $hour, $minute);
    }
}
