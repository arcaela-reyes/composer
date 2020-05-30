<?php
namespace Arcaela;
use Arcaela\Closure;

class Macroable extends Collection{
    public function __construct(...$arguments){
        if(method_exists($this,'lib')) $this->multiMacro($this->lib());
        foreach(['boot','booted'] as $method)
            method_exists($this,$method)?$this->$method():$this->__elseMacros($method)();
    }
}