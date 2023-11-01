<?php
  
namespace App\Enums;

abstract class UserPositionEnum
{
    const  Admin = 1;
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

	public static function toString($searchedValue)
    {
		$search_value = array_column(self::toArray(), null, 'id')[$searchedValue];
		return  $search_value ? $search_value['name'] : false;
    }
}