<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Expressions;

use Codefy\Foundation\Scheduler\Traits\ScheduleValidateAware;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;

final class Hourly implements Expressional
{
    use ScheduleValidateAware;

    /**
     * Sets the job execution time to run once every hour.
     * @throws TypeException
     */
    public static function make(int|string|array $minute = 0): CronExpression
    {
        $validate = self::sequence($minute);

        return At::make(sprintf('%s * * * *', $validate['minute']));
    }
}
