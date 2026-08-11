<?php

namespace App\Enums;

abstract class PlanStatusEnum
{
    const Pending  = 0;
    const Accepted = 1;
    const Rejected = 2;
    const Completed  = 3;
    const Upcoming   = 4;
    const InProgress = 5;
    const Expired     = 6;


    protected static array $labels = [
        self::Pending     => 'Pending',
        self::Accepted    => 'Accepted',
        self::Rejected    => 'Rejected',
        self::Completed   => 'Completed',
        self::Upcoming    => 'Upcoming',
        self::InProgress  => 'In Progress',
        self::Expired     => 'Expired',
    ];

    public static function getConstants(): array
    {
        return (new \ReflectionClass(static::class))->getConstants();
    }

    public static function toArray(): array
    {
        $values = [];

        foreach (static::getConstants() as $name => $value) {
            $values[] = ['id' => $value, 'name' => $name];
        }

        return $values;
    }

    public static function toString($value)
    {
        return static::$labels[$value] ?? false;
    }
}