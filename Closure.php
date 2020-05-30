<?php
namespace Arcaela;
class Closure{
    public static function call($function,$target=null,...$arg){
        $target=is_string($target)?(new $target):$target;
        $function = \is_null($function)?function(){}:(
            is_string($function)?$target->$function:$function
        );
        return $function->bindTo($target,$target)(...$arg);
    }
}