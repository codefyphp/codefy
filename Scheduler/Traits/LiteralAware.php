<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Traits;

use Codefy\Foundation\Scheduler\Expressions\At;
use Codefy\Foundation\Scheduler\Expressions\DayOfWeek\Friday;
use Codefy\Foundation\Scheduler\Expressions\DayOfWeek\Monday;
use Codefy\Foundation\Scheduler\Expressions\DayOfWeek\Saturday;
use Codefy\Foundation\Scheduler\Expressions\DayOfWeek\Sunday;
use Codefy\Foundation\Scheduler\Expressions\DayOfWeek\Thursday;
use Codefy\Foundation\Scheduler\Expressions\DayOfWeek\Tuesday;
use Codefy\Foundation\Scheduler\Expressions\DayOfWeek\Wednesday;
use Codefy\Foundation\Scheduler\Expressions\EveryMinute;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\April;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\August;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\December;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\February;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\January;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\July;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\June;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\March;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\May;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\November;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\October;
use Codefy\Foundation\Scheduler\Expressions\MonthOfYear\September;
use Codefy\Foundation\Scheduler\Expressions\Quarterly;
use Codefy\Foundation\Scheduler\Expressions\WeekDays;
use Codefy\Foundation\Scheduler\Expressions\WeekEnds;
use Cron\CronExpression;

use Qubus\Exception\Data\TypeException;

use function is_string;
use function strtolower;

trait LiteralAware
{
    /**
     * @throws TypeException
     */
    protected function alias(?string $literal): CronExpression
    {
        $literal = is_string($literal) ? strtolower($literal) : null;

        return match ($literal) {
            '@always' => EveryMinute::make(),
            '@weekdays' => WeekDays::make(),
            '@weekends' => WeekEnds::make(),
            '@quarterly' => Quarterly::make(),
            '@sunday', '@sun' => Sunday::make(),
            '@monday', '@mon' => Monday::make(),
            '@tuesday', '@tu', '@tue', '@tues' => Tuesday::make(),
            '@wednesday', '@wed' => Wednesday::make(),
            '@thursday', '@th', '@thu', '@thur', '@thurs' => Thursday::make(),
            '@friday', '@fri' => Friday::make(),
            '@saturday', '@sat' => Saturday::make(),
            '@january', '@jan' => January::make(),
            '@february', '@feb' => February::make(),
            '@march', '@mar' => March::make(),
            '@april', '@apr' => April::make(),
            '@may' => May::make(),
            '@june', '@jun' => June::make(),
            '@july', '@jul' => July::make(),
            '@august', '@aug' => August::make(),
            '@september', '@sept', '@sep' => September::make(),
            '@october', '@oct' => October::make(),
            '@november', '@nov' => November::make(),
            '@december', '@dec' => December::make(),
            default => At::make($literal ?? '* * * * *')
        };
    }
}
