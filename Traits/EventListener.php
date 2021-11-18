<?php

namespace Arcaela\Traits;

trait EventListener {
    public $events = [];
    public function trigger($ev, ...$props){
        if(isset($this->events[$ev]))
            foreach( $this->events[$ev] as $cb )
                $cb( ...$props );
        return $this;
    }
    public function on($ev, callable $cb){
        if( !in_array( $ev, $this->events)) $this->events[$ev] = [];
        array_push($this->events[ $ev ], $cb);
        return $this;
    }
}