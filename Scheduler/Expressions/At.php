<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Expressions;

use Cron\CronExpression;

class At implements Expressional
{
    /**
     * Set the job execution expression.
     */
    public static function make(string $expression): CronExpression
    {
        return new CronExpression($expression);
    }
}
