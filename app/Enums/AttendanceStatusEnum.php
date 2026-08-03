<?php
namespace App\Enums;

enum AttendanceStatusEnum: int
{
    case PRESENT = 1;
    case HOLIDAY = 2;
    case ABSENT = 3;
    case LEAVE_EARLY = 4;
    case LATE_ARRIVAL = 5;
    case LATE_ARRIVAL_LEAVE_EARLY = 6;
    case WEEKEND = 7;
    case LEAVE = 8;

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::HOLIDAY => 'Holiday',
            self::ABSENT => 'Absent',
            self::LEAVE_EARLY => 'Leave Early',
            self::LATE_ARRIVAL => 'Late Arrival',
            self::LATE_ARRIVAL_LEAVE_EARLY => 'Late Arrival & Early Leave',
            self::WEEKEND => 'Weekend',
            self::LEAVE => 'Leave',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PRESENT                    => '#22C55E', // Green
            self::HOLIDAY                    => '#3B82F6', // Blue
            self::ABSENT                     => '#EF4444', // Red
            self::LEAVE_EARLY                => '#F59E0B', // Orange
            self::LATE_ARRIVAL               => '#F97316', // Dark Orange
            self::LATE_ARRIVAL_LEAVE_EARLY   => '#EA580C', // Deep Orange
            self::WEEKEND                    => '#6B7280', // Gray
           self::LEAVE                      => '#06B6D4', // Cyan
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PRESENT                    => "\u{2705}", // ?
            self::HOLIDAY                    => "\u{1F389}", // ??
            self::ABSENT                      => "\u{1F6AB}", // ??
            self::LEAVE_EARLY                 => "\u{21A9}\u{FE0F}", // ??
            self::LATE_ARRIVAL                => "\u{23F0}", // ?
            self::LATE_ARRIVAL_LEAVE_EARLY    => "\u{26A0}\u{FE0F}", // ??
            self::WEEKEND                      => "\u{1F3E0}", // ??
            self::LEAVE                       => "\u{26A0}\u{FE0F}", // ??
        };
    }
}
