<?php
  
namespace App\Enums;

abstract class PlanStatusEnum
{
    const  Pending = 0;
	const  Accepted = 1;
	const  Rejected = 2; 
	const  Completed = 3; 
        const  Upcoming = 4; 


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
		$search_value = array_column(self::toArray(), null, 'id')[$searchedValue]??false;
		return  $search_value ? $search_value['name'] : false;
    }
}