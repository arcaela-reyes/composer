<?php
namespace Arcaela;

use Arcaela\Traits\EventListener;

class Ajax {

    use EventListener;

    public $url = "";
    public function url(string $url){
        $this->url = $url;
        return $this;
    }

    public $method = "GET";
    public function method(string $method){
        $this->method = strtoupper($method);
        return $this;
    }

    public $header=[];
    public function header(...$props){
        if( count($props) === 0) return $this->header;
        else if( count($props) === 1){
            if( is_array( $props[0] ) )
                $this->header = array_merge($this->header, $props[0]);
            else return $this->header[ $props[0] ];
        }
        else $this->header[ $props[0] ] = $props[1];
        return $this;
    }

    public $input=[];
    public function input(...$props){
        if( count($props) === 0) return $this->input;
        else if( count($props) === 1){
            if( is_array( $props[0] ) ){
                if( is_array( $this->input )) $this->input = array_merge($this->input, $props[0]);
                else  $this->input = $props[0];
            }
            else return $this->input[ $props[0] ];
        }
        else $this->input[ $props[0] ] = $props[1];
        return $this;
    }

    public $curl_header = [
        'CURLOPT_HEADER'=>0,
        'CURLOPT_TIMEOUT'=>30,
        'CURLOPT_FAILONERROR'=>1,
        'CURLOPT_RETURNTRANSFER'=>1,
        'CURLOPT_SSL_VERIFYHOST'=>0,
        'CURLOPT_SSL_VERIFYPEER'=>0,
        'CURLOPT_FOLLOWLOCATION'=>1,
    ];
    public function curl(...$props){
        if(!count($props)) return $this->curl_header;
        else if(count($props) === 1){
            if(is_array($props[0]))
                $this->curl_header = array_merge( $this->curl_header, $props[0] );
            else return $this->curl_header[ $props[0] ];
        }
        else $this->curl_header[ $props[0] ] = $props[1];
        return $this;
    }

    public static function init(){ return new static; }

    public static function get(string $url, $then = null, $catch = null){
        return (new static)->url($url)->method("GET")->then($then, $catch);
    }
    public static function post(string $url, array $inputs = []){
        return (new static)->url($url)->method("POST")->input($inputs);
    }

    public static function buildQuery(array $inputs = []){
        $query_array = [];
        foreach ($inputs as $key => $value) {
            if (is_array($value))
                $query_array = array_merge($query_array, array_map(function ($v) use ($key) {
                    return urlencode($key) . '=' . urlencode($v);
                }, $value));
            else $query_array[] = urlencode($key) . '=' . urlencode($value);
        }
        return implode('&', $query_array);        
    }

    public function then($then = null, $catch = null){
        $this->curl_header['CURLOPT_URL'] = $this->url;
        if($this->method==='POST') {
            $this->curl_header["CURLOPT_POST"] = 1;
            $this->curl_header['CURLOPT_POSTFIELDS'] = $this->input;
        } else {
            $this->curl_header['CURLOPT_HTTPGET'] = 1;
            $query_string = is_array($this->input)?static::buildQuery($this->input):$this->input;
            $this->url .= ( substr( $this->url,-1)!=='?' && substr( $query_string, 0, 1)!=='?' ) ? '?' : '';
            $this->curl_header['CURLOPT_URL'] = $this->url . $query_string;
        }
        $this->trigger("before", $this);
        $handler = curl_init();
        $curl_header = [];
        foreach($this->curl_header as $k => $v){
            if(is_string($k)) $curl_header[ constant($k) ]=$v;
            else array_push($curl_header, $v);
        }
        curl_setopt_array( $handler, $curl_header );
        $header = [];
        foreach($this->header as $k => $v){
            if(is_string($k)) $header[$k]=$v;
            else array_push($header, $v);
        }
        curl_setopt( $handler, CURLOPT_HTTPHEADER, $header );
        $data = curl_exec($handler);
        $error = curl_error($handler);
        $httpCode = curl_getinfo($handler, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($handler, CURLINFO_CONTENT_TYPE);
        
        if( preg_match("/\w+\/json/", $contentType) )
            $data = JSON::decode( $data );
        $response = (Object) [
            "url"=>$this->url,
            "body"=>$this->input,
            "headers"=>$this->header,
            "HTTP_CODE"=> $httpCode,
            "CONTENT_TYPE"=> $contentType,
            "ok"=> empty($error) && $httpCode > 100 && $httpCode < 300,
        ];
        curl_close( $handler );

        if( $response->ok ){
            $response->data = $data;
            $this->trigger("success", $data, $response);
            if(is_callable($then)) return $then($data, $response);
        } else {
            $response->message = $error;
            $this->trigger("error", $error, $response);
            if(is_callable($catch)) return $catch($response->message, $response);
        }
        return $response->data ?? null;
    }

}