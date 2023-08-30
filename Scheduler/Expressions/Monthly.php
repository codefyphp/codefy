<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Expressions;

use Codefy\Framework\Scheduler\Traits\ScheduleValidateAware;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;

final class Monthly implements Expressional
{
    use ScheduleValidateAware;

    /**
     * Sets the job execution time to once per month.
     * @throws TypeException
     */
    public static function make(
        int|string|array $month = '*',
        int|string|array $day = 1,
        int|string|array $hour = 0,
        int|string|array $minute = 0
    ): CronExpression {
        if (is_string($hour)) {
            $parts = explode(':', $hour);
            $hour = $parts[0];
            $minute = $parts[1] ?? '0';
        }

        $validate = self::sequence($minute, $hour, $day, $month);

        return At::make(
            sprintf(
                '%s %s %s %s *',
                $validate['minute'],
                $validate['hour'],
                $validate['day'],
                $validate['month']
            )
        );
    }
}
