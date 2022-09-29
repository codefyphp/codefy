<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Traits;

use Qubus\Exception\Data\TypeException;

use function implode;
use function is_array;
use function is_numeric;
use function sprintf;

trait ScheduleValidateAware
{
    /**
     * @throws TypeException
     */
    protected static function sequence(
        int|string|array|null $minute = null,
        int|string|array|null $hour = null,
        int|string|array|null $day = null,
        int|string|array|null $month = null,
        int|string|array|null $weekday = null
    ): array {
        return [
            'minute'  => self::range($minute, 0, 59),
            'hour'    => self::range($hour, 0, 23),
            'day'     => self::range($day, 1, 31),
            'month'   => self::range($month, 1, 12),
            'weekday' => self::range($weekday, 0, 6),
        ];
    }

    /**
     * @throws TypeException
     */
    protected static function range(int|string|array|null $value = null, int $min = 0, int $max = 0): int|string|null
    {
        if ($value === '*') {
            return '*';
        }

        if (is_array($value)) {
            foreach ($value as $v) {
                if (! is_numeric($v) ||
                    ! ($v >= $min && $v <= $max)
                ) {
                    throw new TypeException(
                        sprintf(
                            "Invalid value. It should be '*' or between %s and %s.",
                            $min,
                            $max
                        )
                    );
                }
            }

            $value = implode(',', $value);
            return $value;
        }

        if (null !== $value && (! is_numeric($value) ||
            ! ($value >= $min && $value <= $max))
        ) {
            throw new TypeException(
                sprintf(
                    "Invalid value. It should be '*' or between %s and %s.",
                    $min,
                    $max
                )
            );
        }

        return $value;
    }
}
