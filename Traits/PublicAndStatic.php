<?php


namespace Arcaela\Traits;

trait PublicAndStatic {
    public function __call($name, $arguments) {
        $metod = "_" . $name;
        if( method_exists( $this, $metod ))
            return $this->$metod(...$arguments);
        throw new \BadMethodCallException("El metodo $name no está registrado en este modelo.");        
    }
    public static function __callStatic($name, $arguments) {
        $parent = new self;
        $metod = "_" . $name;
        if( method_exists( $parent, $metod ))
            return $parent->$metod(...$arguments);
        throw new \BadMethodCallException("El metodo $name no está registrado en este modelo.");        
    }
}