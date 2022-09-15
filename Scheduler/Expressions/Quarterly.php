<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Expressions;

use Codefy\Foundation\Scheduler\Traits\ScheduleValidateAware;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;

final class Quarterly implements Expressional
{
    use ScheduleValidateAware;

    /**
     * Sets the job execution time to once per Quarter.
     *
     * @param int|string|array $day Defaults to run the 1st day of each quarter.
     * @param int|string|array $hour
     * @param int|string|array $minute
     * @return CronExpression
     * @throws TypeException
     */
    public static function make(
        int|string|array $day = 1,
        int|string|array $hour = 0,
        int|string|array $minute = 0
    ): CronExpression {
        if (is_string($hour)) {
            $parts = explode(':', $hour);
            $hour = $parts[0];
            $minute = $parts[1] ?? '0';
        }

        $validate = self::sequence($minute, $hour, $day);

        return At::make(
            sprintf(
                '%s %s %s */3 *',
                $validate['minute'],
                $validate['hour'],
                $validate['day']
            )
        );
    }
}
