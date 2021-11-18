<?php
namespace Arcaela;

 class JSON {

    public static function check($var){
        try{ return json_decode( $var, true ); }
        catch(\Exception $e){ return null; }
    }

    public static function utf8($var){
        return is_array($var)?array_combine(array_keys($var),
            array_map(function($v){ return self::utf8($v); }, $var)
        ):(is_string($var)?utf8_encode($var):$var);
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

    public static function decode($json){
        $array = static::check( $json );
        return $array===null ? [] : $array;
    }

}