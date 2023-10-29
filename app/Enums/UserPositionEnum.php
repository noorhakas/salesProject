<?php
  
namespace App\Enums;

abstract class UserPositionEnum
{
    const  Owner = 1;
    const  Manager = 2;
	const  MedicalRep = 3; 

 static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

	public static function toArray()
    {
        $values = [];
        foreach (self::getConstants() as  $name => $value) {
             array_push($values,["id"=>$value,"name"=>$name ]);
        }
        return $values;
    }
}