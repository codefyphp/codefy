<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Expressions;

use Codefy\Framework\Scheduler\Traits\ScheduleValidateAware;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

final class EveryMinute implements Expressional
{
    use ScheduleValidateAware;

    /**
     * Set the job execution time to every minute.
     *
     * @param int|string|array|null $minute If not null, specifies that the job will run every $minute minutes.
     * @throws TypeException
     */
    public static function make(int|string|array|null $minute = null): CronExpression
    {
        $expression = '*';
        if (!is_null__($minute) && (is_string($minute) || is_int($minute))) {
            $validate = self::sequence($minute);
            $expression = '*/' . $validate['minute'];
        }

        if (is_array($minute)) {
            $validate = self::sequence($minute);
            $expression = $validate['minute'];
        }

        return At::make(sprintf('%s * * * *', $expression));
    }
}
