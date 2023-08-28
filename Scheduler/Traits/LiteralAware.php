<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Traits;

use Codefy\Framework\Scheduler\Expressions\At;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Friday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Monday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Saturday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Sunday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Thursday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Tuesday;
use Codefy\Framework\Scheduler\Expressions\DayOfWeek\Wednesday;
use Codefy\Framework\Scheduler\Expressions\EveryMinute;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\April;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\August;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\December;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\February;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\January;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\July;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\June;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\March;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\May;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\November;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\October;
use Codefy\Framework\Scheduler\Expressions\MonthOfYear\September;
use Codefy\Framework\Scheduler\Expressions\Quarterly;
use Codefy\Framework\Scheduler\Expressions\WeekDays;
use Codefy\Framework\Scheduler\Expressions\WeekEnds;
use Cron\CronExpression;

use Qubus\Exception\Data\TypeException;

use function is_string;
use function strtolower;

trait LiteralAware
{
    /**
     * @throws TypeException
     */
    public function alias(?string $literal): CronExpression
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
