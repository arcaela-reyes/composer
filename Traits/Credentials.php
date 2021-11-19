<?php



namespace Arcaela\Traits;


trait Credentials {
    protected $credential = [ 'public'=>'', 'secret'=>'' ];
    public function _use(array $credentials = []){
        $this->credential = $credentials;
        return $this;
    }
    private function select(string $key = ''){
        return $this->credential[ strtolower( $key ) ] ?? '';
    }
}