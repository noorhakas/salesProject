<?php
  
namespace App\Enums;

enum UserTypeEnum: int
{

// 	enum ProductStatusEnum:string {
//     case Pending = 'pending';
//     case Active = 'active';
//     case Inactive = 'inactive';
//     case Rejected = 'rejected';
//   }

    case  MedicalRep = 1;
    case  Manager = 2;

    public function toString(): string
    {
        return match ($this) {
            self::MedicalRep    => 'MedicalRep',
            self::Manager    => 'District Manager',
            default => '',
        };
    }
}