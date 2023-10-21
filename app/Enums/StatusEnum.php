<?php
  
namespace App\Enums;

enum StatusEnum: int
{
    case  Active = 1;
    case  Inactive = 2;

    public function toString(): string
    {
        return match ($this) {
            self::Active    => 'Active',
            self::Manager    => 'Inactive',
            default => '',
        };
    }
}