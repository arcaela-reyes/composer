<?php


namespace Arcaela;


/**
 * Documentation https://binance-docs.github.io/apidocs/spot/en
 */
class Binance {

    use Traits\Credentials;
    use Traits\PublicAndStatic;

    private function getURL(string $pathname = ''){
        $host = rand(0, 3);
        $host = $host===0 ? '' : $host;
        return "https://api$host.binance.com/" . $pathname;
    }

    public function _public(string $pathname = '', array $inputs = [], string $method = 'GET'){
        return Ajax::init()
            ->header([ 'X-MBX-APIKEY: '. $this->select('public') ])
            ->url( $this->getURL($pathname) )
            ->method( $method )
            ->input( $inputs );
    }
      
    public function _private(string $pathname = '', array $inputs = [], string $method = 'GET'){
        $inputs['timestamp'] = round(microtime(true) * 1000);
        $inputs['signature'] = hash_hmac("sha256", Ajax::buildQuery($inputs), $this->select('secret') );
        return $this->_public($pathname, $inputs, $method);
    }

}