<?php
  
namespace App\Enums;

abstract class ScheduleStatusEnum
{
    const  NOACTION = ["id"=>0 ,"color"=>"#00FFFF"];
    const  Pending = ["id"=>1 ,"color"=>"#ff1493"];
	const  Confirmed = ["id"=>2 ,"color"=>"#adff2f"]; 
	const  Visited = ["id"=>3 ,"color"=>"#228b22"];
	const  Holiday = ["id"=>4 ,"color"=>"rgb(163 130 130)"];

 static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

	public static function toArray()
    {
        $values = [];
        foreach (self::getConstants() as  $name => $value) {
			if($value["id"] !== 0)
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