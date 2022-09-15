<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Expressions;

use Cron\CronExpression;
use DateTime;
use Exception;

use function sprintf;

final class Date implements Expressional
{
    /**
     * Set the job execution expression.
     * @throws Exception
     */
    public static function make(string|DateTime $datetime): CronExpression
    {
        if (! $datetime instanceof DateTime) {
            $datetime = new DateTime($datetime);
        }

        return At::make(
            sprintf(
                '%s %s %s %s *',
                $datetime->format('i'),
                $datetime->format('H'),
                $datetime->format('d'),
                $datetime->format('m')
            )
        );
    }
}
