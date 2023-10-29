<?php
  
namespace App\Enums;

abstract class StatusEnum
{
    const  Active = 1;
    const  Inactive = 0;

 static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}