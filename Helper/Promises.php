<?php

namespace Arcaela\Helper;
use ErrorException;
use Arcaela\Closure;
class Promises{
    public $__LastPromise_ResponseGenerated = null;
    public static function lib(){ return (new static)->lb();}
    public function lb(){
        return [
            "promise"=>function(\Closure $callback){
                try{
                    $this->__LastPromise_ResponseGenerated=Closure::call($callback,$this);
                }catch(ErrorException $e){
                    $this->__LastPromise_ResponseGenerated=$e;
                };
                return $this;
            },
            "then"=>function(\Closure $then,\Closure $catch=null){
                $data=$this->__LastPromise_ResponseGenerated;
                $error=($data instanceof ErrorException);
                $arguments=($error&&!is_callable($catch))?[null,$data]:[$data,null];
                $closure=($error)?(is_callable($catch)?$catch:$then):$then;
                return Closure::call($closure,$this,...$arguments);
            },
        ];
    }
}
