<?php

namespace App\Enums;

enum DayOffEnum: int
{
    case SUNDAY    = 1;
    case MONDAY    = 2;
    case TUESDAY   = 3;
    case WEDNESDAY = 4;
    case THURSDAY  = 5;
    case FRIDAY    = 6;
    case SATURDAY  = 7;

    public function label(): string
    {
        return match ($this) {
            self::SUNDAY    => __('users.labels.sunday'),
            self::MONDAY    => __('users.labels.monday'),
            self::TUESDAY   => __('users.labels.tuesday'),
            self::WEDNESDAY => __('users.labels.wednesday'),
            self::THURSDAY  => __('users.labels.thursday'),
            self::FRIDAY    => __('users.labels.friday'),
            self::SATURDAY  => __('users.labels.saturday'),
        };
    }

    public function toCarbonDay(): int
    {
        return match ($this) {
            self::SUNDAY    => 0,
            self::MONDAY    => 1,
            self::TUESDAY   => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY  => 4,
            self::FRIDAY    => 5,
            self::SATURDAY  => 6,
        };
    }

    public static function fromCarbon(int $carbonDay): self
    {
        return match ($carbonDay) {
            0 => self::SUNDAY,
            1 => self::MONDAY,
            2 => self::TUESDAY,
            3 => self::WEDNESDAY,
            4 => self::THURSDAY,
            5 => self::FRIDAY,
            6 => self::SATURDAY,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [
                $case->value => $case->label()
            ])
            ->toArray();
    }
}