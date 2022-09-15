<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Expressions;

use Codefy\Foundation\Scheduler\Traits\ScheduleValidateAware;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;

use function explode;
use function is_string;
use function sprintf;

final class Weekly implements Expressional
{
    use ScheduleValidateAware;

    /**
     * Sets the job execution time to once per week.
     * @throws TypeException
     */
    public static function make(
        int|string|array $weekday = 0,
        int|string|array $hour = 0,
        int|string|array $minute = 0
    ): CronExpression {
        if (is_string($hour)) {
            $parts = explode(':', $hour);
            $hour = $parts[0];
            $minute = $parts[1] ?? '0';
        }

        $validate = self::sequence($minute, $hour, null, null, $weekday);

        return At::make(
            sprintf(
                '%s %s * * %s',
                $validate['minute'],
                $validate['hour'],
                $validate['weekday']
            )
        );
    }
}
