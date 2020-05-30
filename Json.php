<?php
namespace Arcaela;

 class Json{
	public static function isJson(...$a){
		return static::is_json(...$a);
	}
	public static function is_json(...$json){
		try{return json_decode(...$json);}
		catch(Exception $e){return false;}
	}
	public static function utf8($data=[]){
		return is_array($data) ? array_combine(array_keys($data), array_map(function($t){
			return self::utf8($t);
		},$data)):(is_string($data)?utf8_encode($data):$data);
	}
	public static function encode($object=array(),$print=false,$pretty=true){
		$object = json_encode((Array)self::utf8($object), (!empty($pretty) ? JSON_PRETTY_PRINT : false));
		switch ($print) {
			case true:
				print_r($object);
				break;
			default:
				return $object;
				break;
		}
	}
	public static function decode($json=false,$partial=true){
		try {
			$s = is_object($json) ? (Array)$json : (is_array($json) ? $json : json_decode($json,$partial));
			return empty($s) ? array() : $s;
		} catch (Exception $e) {return array();}
	}
}