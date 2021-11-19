<?php

namespace Arcaela;


class LocalBitcoin {

    use Traits\Credentials;
    use Traits\PublicAndStatic;


    private static function boundary($boundary, $fields, $files){
        $data = '';
        $eol = "\r\n";
        $delimiter = '-------------' . $boundary;
        foreach ($fields as $name => $content)
            $data .= "--" . $delimiter . $eol . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol . $content . $eol;
        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol . 'Content-Disposition: form-data; name="document"; filename="' . $name . '"' . $eol . 'Content-Type: image/png'.$eol . 'Content-Transfer-Encoding: binary'.$eol;
            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--".$eol;
        return $data;
    }
    public function _query(string $pathname, array $inputs = [], string $method = 'GET'){
        $headers = [];
        if( $method === 'POST' && !empty( $inputs['document']) ){
            $boundary = uniqid();
            $bn = basename($inputs['document']);
            $delimiter = '-------------' . $boundary;
            $fn[$bn] = file_get_contents($inputs['document']);
            $inputs = static::boundary($boundary, $inputs, $fn);
            $headers[] = "Content-Type: multipart/form-data; boundary=$delimiter";
        }
        $mt = explode(' ',microtime());
        $nonce = $mt[1].substr($mt[0],2,6);
        $headers[] = "Apiauth-Nonce: ".$nonce;
        $headers[] = "Apiauth-Key: ".$this->select('public');
        $headers[] = "Apiauth-Signature: ".strtoupper(hash_hmac('sha256',$nonce.$this->select('public').$pathname.Ajax::buildQuery($inputs), $this->select('secret')));
        return Ajax::init()
            ->input($inputs)
            ->method($method)
            ->header($headers)
            ->url("https://localbitcoins.com/". $pathname);
    }

    public function wallet($then=null, $catch=null){
        return $this->_query("/api/wallet-balance/")->then(function(Object $response) use($then){
            $data = $response->data['data'] ?? [
                'receiving_address'=>'',
                'total'=>[
                    'balance'=>0,
                    'sendable'=>0,
                ],
            ];
            $data['total']['balance'] = (float) $data['total']['balance'];
            if( is_callable( $then )) return $then( $data );
            return $data;
        },$catch);
    }

}