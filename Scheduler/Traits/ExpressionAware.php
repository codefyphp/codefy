<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Traits;

use Codefy\Framework\Scheduler\Schedule;
use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use DateTimeZone;
use Qubus\Exception\Data\TypeException;
use Qubus\Inheritance\InvokerAware;
use Qubus\Support\DateTime\QubusDateTime;

use function array_intersect_key;
use function date;
use function date_parse;
use function explode;
use function func_get_args;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;
use function strtotime;

trait ExpressionAware
{
    use InvokerAware;
    use LiteralAware;

    protected CronExpression|string $expression = '* * * * *';

    /**
     * Position of cron fields.
     *
     * @var array<string,int>
     */
    protected $fieldsPosition = [
        'minute'  => CronExpression::MINUTE,
        'hour'    => CronExpression::HOUR,
        'day'     => CronExpression::DAY,
        'month'   => CronExpression::MONTH,
        'weekday' => CronExpression::WEEKDAY,
    ];

    /**
     * The Cron expression representing the task's frequency.
     * @throws TypeException
     */
    public function cron(string $expression = '* * * * *'): self
    {
        $expression = $this->alias($expression)->getExpression();

        $this->expression = new CronExpression($expression);

        return $this;
    }

    /**
     * Determine if the filters pass.
     */
    public function filtersPass(): bool
    {
        foreach ($this->filters as $callback) {
            if (! $this->call($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if ($this->call($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Schedule task to run between start and end time.
     */
    public function between(string $startTime, string $endTime): self
    {
        return $this->when($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule task that doesn't fall between start and end time.
     */
    public function unlessBetween(string $startTime, string $endTime): self
    {
        return $this->skip($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule task to run between start and end time.
     */
    private function inTimeInterval(string $startTime, string $endTime): Closure
    {
        [$now, $startTime, $endTime] = [
            QubusDateTime::now($this->timezone),
            QubusDateTime::parse($startTime, $this->timezone),
            QubusDateTime::parse($endTime, $this->timezone),
        ];

        if ($endTime->lessThan($startTime)) {
            if ($startTime->greaterThan($now)) {
                $startTime->subDay(1);
            } else {
                $endTime->addDay(1);
            }
        }

        return fn () => $now->between($startTime, $endTime);
    }

    /**
     * Schedule the task to run hourly.
     */
    public function hourly(int|string $minute = 1): self
    {
        $value = (int) $minute === 1 || (int) $minute === 0 ? '0' : $minute;

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], '*')
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $value);
    }

    /**
     * Schedule the task to run daily.
     */
    public function daily(?string $time = null): self
    {
        $minute = $hour = 0;

        if (! is_null__($time)) {
            [$minute, $hour] = $this->parseTime($time);
        }

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], (string) $hour)
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $minute);
    }

    /**
     * Schedule the task to run on a certain date.
     */
    public function on(string $date): self
    {
        $parsedDate = date_parse($date);
        if (is_false__($parsedDate)) {
            $parsedDate = [];
        }

        $segments = array_intersect_key($parsedDate, $this->fieldsPosition);

        if ($parsedDate['year']) {
            $this->skip(static fn () => (int) date('Y') !== $parsedDate['year']);
        }

        foreach ($segments as $key => $value) {
            if (! is_false__($value)) {
                $this->spliceIntoPosition((int) $this->fieldsPosition[$key], (string) $value);
            }
        }

        return $this;
    }

    /**
     * Schedule the command at a given time.
     */
    public function at(?string $time = null): self
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the task to run daily at a given time (10:00, 19:30, etc).
     */
    public function dailyAt(?string $time = null): self
    {
        $minute = $hour = 0;

        if (! is_null__($time)) {
            [$minute, $hour] = $this->parseTime($time);
        }

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], (string) $hour)
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $minute);
    }

    /**
     * Schedule the task to run twice daily.
     *
     * @param int $first First hour.
     * @param int $second Second hour.
     */
    public function twiceDaily(int $first = 1, int $second = 13): self
    {
        return $this->twiceDailyAt($first, $second, 0);
    }

    /**
     * Schedule the task to run twice daily at a given minute.
     *
     * @param int $first First hour.
     * @param int $second Second hour.
     * @param int $minute Minute.
     */
    public function twiceDailyAt(int $first = 1, int $second = 13, int $minute = 0): self
    {
        $hours = (string) $first . ',' . $second;

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], $hours)
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $minute);
    }

    /**
     * Schedule the task to run only on weekdays.
     */
    public function weekdays(?string $time = null): self
    {
        $minute = $hour = 0;

        if (! is_null__($time)) {
            [$minute, $hour] = $this->parseTime($time);
        }

        $weekdays = Schedule::MONDAY . '-' . Schedule::FRIDAY;

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['weekday'], (string) $weekdays)
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], (string) $hour)
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $minute);
    }

    /**
     * Schedule the task to run only on weekdays.
     */
    public function weekends(?string $time = null): self
    {
        $minute = $hour = 0;

        if (! is_null__($time)) {
            [$minute, $hour] = $this->parseTime($time);
        }

        $weekends = Schedule::SATURDAY . ',' . Schedule::SUNDAY;

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['weekday'], (string) $weekends)
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], (string) $hour)
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $minute);
    }

    /**
     * Schedule the task to run only on Mondays.
     */
    public function mondays(?string $time = null): self
    {
        return $this->setDayOfWeek(Schedule::MONDAY, $time);
    }

    /**
     * Schedule the task to run only on Tuesdays.
     */
    public function tuesdays(?string $time = null): self
    {
        return $this->setDayOfWeek(Schedule::TUESDAY, $time);
    }

    /**
     * Schedule the task to run only on Wednesdays.
     */
    public function wednesdays(?string $time = null): self
    {
        return $this->setDayOfWeek(Schedule::WEDNESDAY, $time);
    }

    /**
     * Schedule the task to run only on Thursdays.
     */
    public function thursdays(?string $time = null): self
    {
        return $this->setDayOfWeek(Schedule::THURSDAY, $time);
    }

    /**
     * Schedule the task to run only on Fridays.
     */
    public function fridays(?string $time = null): self
    {
        return $this->setDayOfWeek(Schedule::FRIDAY, $time);
    }

    /**
     * Schedule the task to run only on Saturdays.
     */
    public function saturdays(?string $time = null): self
    {
        return $this->setDayOfWeek(Schedule::SATURDAY, $time);
    }

    /**
     * Schedule the task to run only on Sundays.
     */
    public function sundays(?string $time = null): self
    {
        return $this->setDayOfWeek(Schedule::SUNDAY, $time);
    }

    /**
     * Schedule the task to run weekly.
     */
    public function weekly(): self
    {
        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['weekday'], '0')
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], '0')
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], '0');
    }

    /**
     * Schedule the task to run weekly on a given day and time.
     */
    public function weeklyOn(int|string $day, string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition((int) $this->fieldsPosition['weekday'], (string) $day);
    }

    /**
     * Schedule the task to run monthly.
     */
    public function monthly(): self
    {
        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['day'], '1')
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], '0')
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], '0');
    }

    /**
     * Schedule the task to run monthly on a given day and time.
     */
    public function monthlyOn(int|string $dayOfMonth, string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition((int) $this->fieldsPosition['day'], (string) $dayOfMonth);
    }

    /**
     * Schedule the task to run monthly on a given day and time.
     */
    public function lastDayOfTheMonth(string $time = '0:0'): self
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(
            (int) $this->fieldsPosition['day'],
            (string) QubusDateTime::now()->endOfMonth()->day
        );
    }

    /**
     * Schedule the task to run quarterly.
     */
    public function quarterly(int|string|array $day = 1, ?string $time = null): self
    {
        $minute = $hour = 0;

        if (! is_null__($time)) {
            [$minute, $hour] = $this->parseTime($time);
        }

        $day = is_array($day) ? implode(',', $day) : $day;

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['month'], '1-12/3')
            ->spliceIntoPosition((int) $this->fieldsPosition['day'], (string) $day)
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], (string) $hour)
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $minute);
    }

    /**
     * Schedule the task to run yearly.
     */
    public function yearly(): self
    {
        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['month'], '1')
            ->spliceIntoPosition((int) $this->fieldsPosition['day'], '1')
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], '0')
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], '0');
    }

    /**
     * Schedule the task to run yearly.
     */
    public function yearlyOn(int $month, int $day, ?string $time = null): self
    {
        $this->dailyAt($time);

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['month'], (string) $month)
            ->spliceIntoPosition((int) $this->fieldsPosition['day'], (string) $day);
    }

    /**
     * Set the days of the week the command should run on.
     */
    public function days(mixed $days): self
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition((int) $this->fieldsPosition['weekday'], implode(',', $days));
    }

    /**
     * Set hour for the cron job.
     */
    public function hour(mixed $value): self
    {
        $value = is_array($value) ? $value : func_get_args();

        return $this->spliceIntoPosition((int) $this->fieldsPosition['hour'], implode(',', $value));
    }

    /**
     * Set minute for the cron job.
     */
    public function minute(mixed $value): self
    {
        $value = is_array($value) ? $value : func_get_args();

        return $this->spliceIntoPosition((int) $this->fieldsPosition['minute'], implode(',', $value));
    }

    /**
     * Set day of the month for the cron job.
     */
    public function dayOfMonth(mixed $value): self
    {
        $value = is_array($value) ? $value : func_get_args();

        return $this->spliceIntoPosition((int) $this->fieldsPosition['day'], implode(',', $value));
    }

    /**
     * Set month for the cron job.
     */
    public function month(mixed $value): self
    {
        $value = is_array($value) ? $value : func_get_args();

        return $this->spliceIntoPosition((int) $this->fieldsPosition['month'], implode(',', $value));
    }

    /**
     * Set dah of the week for the cron job.
     */
    public function dayOfWeek(mixed $value): self
    {
        $value = is_array($value) ? $value : func_get_args();

        return $this->spliceIntoPosition((int) $this->fieldsPosition['weekday'], implode(',', $value));
    }

    /**
     * Set the timezone the date should be evaluated on.
     *
     * @return $this
     */
    public function timezone(DateTimeZone|string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Another way to the frequency of the cron job.
     *
     * @param string|null $unit (minute, hour, day, month or weekday)
     */
    public function every(?string $unit = null, float|int|null $value = null): self
    {
        if (is_null__($unit) || ! isset($this->fieldsPosition[$unit])) {
            return $this;
        }

        $value = 1 === (int) $value ? '*' : '*/' . $value;

        return $this->spliceIntoPosition($this->fieldsPosition[$unit], $value);
    }

    /**
     * Schedule task to run every minute of every $minute minutes.
     */
    public function everyMinute(string|int $minute = 1): self
    {
        $value = (int) $minute === 1 || (int) $minute === 0 ? '*' : sprintf('*/%s', (string) $minute);

        return $this->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $value);
    }

    /**
     * Schedule task to run every hour or every $hour hour and $minute minutes.
     */
    public function everyHour(string|int $hour = 1, int $minute = 0): self
    {
        $hour = (int) $hour === 1 || (int) $hour === 0 ? '*' : sprintf('*/%s', (string) $hour);
        $minute = (int) $minute === 1 || (int) $minute === 0 ? 0 : $minute;

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], (string) $hour)
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $minute);
    }

    /**
     * Determine if the Cron expression passes.
     */
    protected function expressionPasses(string|DateTimeZone|null $timezone = null): bool
    {
        $now = Carbon::now();
        $now = $now->setTimezone($timezone);

        if ($this->timezone) {
            $taskTimeZone = is_object($this->timezone) && $this->timezone instanceof DateTimeZone
            ? $this->timezone
                ->getName()
            : $this->timezone;

            $now = $now->setTimezone(
                new DateTimeZone(
                    $taskTimeZone
                )
            );
        }

        return $this->expression->isDue($now->format('Y-m-d H:i:s'));
    }

    /**
     * Splice the given value into the given position of the expression.
     */
    protected function spliceIntoPosition(int $position, string $value): self
    {
        /*if ($this->expression instanceof CronExpression) {
            $expression = $this->expression->getExpression();
        }

        if (is_string($this->expression)) {
            $expression = $this->expression;
        }*/

        $expression = match (true) {
            $this->expression instanceof CronExpression => $this->expression->getExpression(),
            is_string($this->expression) => $this->expression
        };

        $segments = explode(' ', $expression);

        $segments[$position] = $value;

        return $this->cron(implode(' ', $segments));
    }

    /**
     * Internal function used by the everyMonday, etc functions.
     */
    protected function setDayOfWeek(int|string $day, ?string $time = null): self
    {
        $minute = $hour = '0';

        if (! is_null__($time)) {
            [$minute, $hour] = $this->parseTime($time);
        }

        return $this
            ->spliceIntoPosition((int) $this->fieldsPosition['weekday'], (string) $day)
            ->spliceIntoPosition((int) $this->fieldsPosition['hour'], (string) $hour)
            ->spliceIntoPosition((int) $this->fieldsPosition['minute'], (string) $minute);
    }

    /**
     * Parses a time string (like 4:08 pm) into minutes and hours.
     */
    protected function parseTime(string $time): array
    {
        $time = strtotime($time);

        return [
            (int) date('i', $time), // minutes
            date('G', $time),
        ];
    }
}
