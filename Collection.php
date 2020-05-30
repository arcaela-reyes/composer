<?php
namespace Arcaela;

class Collection extends \Illuminate\Support\Collection{
    // macros
    public static $myMacros=[];
    public function __getMacro($name=null){return static::$myMacros[$name]??null;}
    public function multiMacro(array $functions){
        static::$myMacros=array_merge(static::$myMacros,$functions);
        return $this;
    }
    public static function macro($name,$function){ static::$myMacros[$name]=$function; }
    public function extends($name,$function){
        static::$myMacros[$name]=$function;
        return $this;
    }
    public function __elseMacros($a,$b=null){
        return (\is_string($a)?$this->__getMacro($a):(\is_callable($a)?$a:null))??
            (\is_string($b)?$this->__getMacro($b):(\is_callable($b)?$b:function(){}));
    }
    // Items
    public function items(...$items){
        return blank($items)?$this->items:(
            count($items)==1?$this->items[$items[0]]:($this->items[$items[0]]=$items[1])
        );
    }
    public function merge($items){
        $this->items=array_merge($this->items,$items);
        return $this;
    }
    public function loadItems($items){
        $this->items=$this->getArrayableItems($items);
        return $this;
    }
    // Observers
    public function __get($key){
        if($__ob__=(static::$myMacros["(GET)$key"]??null)) return Closure::call($__ob__,$this);
        return isset($this->$key)?$this->key:($this->items[$key]??null);
    }
    public function __set($key,$value){
        if($__ob__=(static::$myMacros['(SET)'.$key]??null)) {return $this->$key=Closure::call($__ob__,$this,$value,$key);}
        return ($this->$key=$value);
    }
    // Callables
    public function __call($method,$arg){
        $method=$this->__elseMacros($method,'__call');
        return $method?Closure::call($method,$this,...$arg):parent::__callStatic($method,$arg);
    }
    public static function __callStatic($method,$arg){
        $parent=new static;
        return Closure::call(($parent->__elseMacros($method,"__callStatic")??function(...$arg)use($method){
            throw new \BadMethodCallException("El metodo '$method' no está definido en esta librería.", 1);
        }),$parent,...$arg);
    }
}
