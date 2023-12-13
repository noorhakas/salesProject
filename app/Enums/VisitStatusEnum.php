<?php
  
namespace App\Enums;

abstract class VisitStatusEnum 
{
    const  Pending = ["id"=>0 ,"color"=>"rgb(204 64 140)"];
	const  Visited = ["id"=>2 ,"color"=>"#228b22"];
	const  Holiday = ["id"=>3 ,"color"=>"rgb(93 67 81)"];
	const  Missed = ["id"=>5 ,"color"=>"rgb(163 130 130)"];

 static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

	public static function toArray()
    {
        $values = [];
        foreach (self::getConstants() as  $name => $value) {
             array_push($values,["id"=>$value["id"],"name"=>$name ,"color"=>$value["color"]]);
        }
        return $values;
    }


	public static function toString($searchedValue)
    {
		$search_value = array_column(self::toArray(), null, 'id')[$searchedValue]??false;
		return  $search_value ? $search_value['name'] : false;
    }
}