<?php
namespace Arcaela;

use BadMethodCallException;

if(!defined('SSL_VERIFYPEER')) define('SSL_VERIFYPEER',true);
if(!defined('SSL_VERIFYHOST')) define('SSL_VERIFYHOST',true);

class LocalBitcoin {

    static function build_data_files($boundary, $fields, $files){
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
    
    protected $credential = null;
    public function _use(array $credentials = ['private', 'secret']){
        $this->credential = $credentials;
        return $this;
    }

    public function _query(string $pathname, array $inputs = [], array $params = []){
        $api_get 	= ['/api/ads/','/api/ad-get/{ad_id}/','/api/ad-get/','/api/payment_methods/','/api/payment_methods/{countrycode}/','/api/countrycodes/','/api/currencies/','/api/places/','/api/contact_messages/{contact_id}/','/api/contact_info/{contact_id}/','/api/contact_info/','/api/account_info/{username}','/api/dashboard/','/api/dashboard/released/','/api/dashboard/canceled/','/api/dashboard/closed/','/api/myself/','/api/notifications/','/api/real_name_verifiers/{username}/','/api/recent_messages/','/api/wallet/','/api/wallet-balance/','/api/wallet-addr/','/api/merchant/invoices/','/api/merchant/invoice/{invoice_id}/','/api/contact_message_attachment/22791801/139109770/'];
        $api_post 	= ['/api/ad/{ad_id}/','/api/ad-create/','/api/ad-equation/{ad_id}/','/api/ad-delete/{ad_id}/','/api/feedback/{username}/','/api/contact_release/{contact_id}/','/api/contact_release_pin/{contact_id}/','/api/contact_mark_as_paid/{contact_id}/','/api/contact_message_post/{contact_id}/','/api/contact_dispute/{contact_id}/','/api/contact_cancel/{contact_id}/','/api/contact_fund/{contact_id}','/api/contact_mark_realname/{contact_id}/','/api/contact_mark_identified/{contact_id}/','/api/contact_create/{ad_id}/','/api/logout/','/api/notifications/mark_as_read/{notification_id}/','/api/pincode/','/api/wallet-send/','/api/wallet-send-pin/','/api/merchant/new_invoice/','/api/merchant/delete_invoice/{invoice_id}/'];
        $api_public	= ['/buy-bitcoins-with-cash/{location_id}/{location_slug}/.json','/sell-bitcoins-for-cash/{location_id}/{location_slug}/.json','/buy-bitcoins-online/{countrycode:2}/{country_name}/{payment_method}/.json','/buy-bitcoins-online/{countrycode:2}/{country_name}/.json','/buy-bitcoins-online/{currency:3}/{payment_method}/.json','/buy-bitcoins-online/{currency:3}/.json','/buy-bitcoins-online/{payment_method}/.json','/buy-bitcoins-online/.json','/sell-bitcoins-online/{countrycode:2}/{country_name}/{payment_method}/.json','/sell-bitcoins-online/{countrycode:2}/{country_name}/.json','/sell-bitcoins-online/{currency:3}/{payment_method}/.json','/sell-bitcoins-online/{currency:3}/.json','/sell-bitcoins-online/{payment_method}/.json','/sell-bitcoins-online/.json','/bitcoinaverage/ticker-all-currencies/','/bitcoincharts/{currency}/trades.json','/bitcoincharts/{currency}/orderbook.json'];
        $headers = [];
        $url = "https://localbitcoins.com/". str_replace(array_map(function($k){return"{$k}";},array_keys($params)),array_values($params),$pathname);

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url);
        $body = http_build_query($inputs, '', '&');

        if(in_array($pathname, $api_post)){
            if($inputs['document']){
                $bn = basename($inputs['document']);
                $fn[$bn] = file_get_contents($inputs['document']);
                $boundary = uniqid();
                $delimiter = '-------------' . $boundary;
                $body = static::build_data_files($boundary, $inputs, $fn);
                $headers[] = "Content-Type: multipart/form-data; boundary=$delimiter";
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        else { $url .= "?" . $body; curl_setopt($ch, CURLOPT_HTTPGET, true); }

        $cert = (Object) ($this->credential ?? [ "secret"=>"", "public"=>"" ]);
        $lbtkey = $cert->public;
        $keysec = $cert->secret;
        $mt = explode(' ', microtime());
        $nonce = $mt[1].substr($mt[0], 2, 6);
        $headers[] = "Apiauth-Key: $lbtkey";
        $headers[] = "Apiauth-Nonce: ".$nonce;
        $headers[] = "Apiauth-Signature: " . strtoupper(hash_hmac('sha256', $nonce . $lbtkey . $pathname . $body, $keysec));

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = curl_exec($ch);
        $error = curl_error($ch);
        $response = (Object) [
            "url"=> $url,
            "error"=> $error,
            "params"=> $body,
            "data"=> JSON::check($data) ?? $data,
            'statusCode' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'content-type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
        ];
        curl_close( $ch );
        return $response;
    }

    public function _balance($then=null, $catch=null){
        $data = $this->_query("/api/wallet-balance/");
        if( !$data->error ){
            $data->data = $data->data['data'] ?? null;
            $data = [
                'balance'=>$data->data ? (float) $data->data['total']['balance'] : 0,
                'address'=>$data->data ? $data->data['receiving_address'] : 'UNKNOWM',
            ];
            if( is_callable( $then )) return $then( $data );
        }
        else if( is_callable( $catch )) return $catch( $data );
        return $data;
    }

    public function __call($name, $arguments) {
        $metod = "_" . $name;
        if( method_exists( $this, $metod ))
            return $this->$metod(...$arguments);
        throw new BadMethodCallException("El metodo $name no está registrado en este modelo.");        
    }

    public static function __callStatic($name, $arguments) {
        $parent = new self;
        $metod = "_" . $name;
        if( method_exists( $parent, $metod ))
            return $parent->$metod(...$arguments);
        throw new BadMethodCallException("El metodo $name no está registrado en este modelo.");        
    }

}