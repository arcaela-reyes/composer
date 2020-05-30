<?php
    namespace Arcaela\Traits;
    use \Illuminate\Support\Arr;
    
trait Basics{
    public function boot(){
        return $this->multiMacro([
            'const'=>function($key,...$value){
                return blank($value)?$this->$key:($this->$key=$value[0]);
            },
            'set'=>function($key,...$value){
                if(blank($value)) return $this->$key;
                $this->$key=$value[0];
                return $this;
            },
            'setOnEmpty'=>function($key,$value){
                return !isset($this->$key)?$this->set($key,$value):$this;
            },
            'push'=>function($key,$value){
                array_push($this->$key,$value);
                return $this;
            },
            'then'=>function($call,...$arguments){
                return \Arcaela\Closure::call($call,$this,...(count($arguments)?$arguments:[$this]));
            },
            'call'=>function($call,...$keys){
                $this->then($call,...$keys);
                return $this;
            },
        ]);
    }


}