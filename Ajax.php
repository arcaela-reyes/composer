<?php
namespace Arcaela;
use Illuminate\Support\Facades\Cache;
use \Illuminate\Support\Arr;


class Ajax extends Macroable{
    use Traits\Basics;
    public $header=[];
    public $input = [];
    public $events = [];
    public $response = [];

    public $method = 'GET';
    public $cache = [ 'allow'=>false,'expire'=>'+3 minutes' ];
    public $curl_header = [
        'CURLOPT_RETURNTRANSFER'=>1,
        'CURLOPT_FAILONERROR'=>1,
        'CURLOPT_SSL_VERIFYHOST'=>0,
        'CURLOPT_SSL_VERIFYPEER'=>0,
        'CURLOPT_FOLLOWLOCATION'=>1,
        'CURLOPT_TIMEOUT'=>30,
        'CURLOPT_HEADER'=>0
    ];

    function lib(){
        return [
            'curl_header'=>function(...$keys){
                $store = $this->curl_header??[];
                if(blank($keys)) return $store;
                if(count($keys) == 1&& is_string($keys[0])) return Arr::get($store, $keys[0]);
                else if(count($keys) == 1&& is_array($keys[0])) $store = array_merge($store, $keys[0]);
                else if(count($keys)>1 && is_string($keys[0])) Arr::set($store, $keys[0], $keys[1]);
                $this->curl_header = $store;
                return $this;
            },
            'header'=>function(...$keys){
                $store = $this->header??[];
                if(blank($keys)) return $store;
                if(count($keys) == 1&& is_string($keys[0])) return Arr::get($store, $keys[0]);
                else if(count($keys) == 1&& is_array($keys[0])) $store = array_merge($store, $keys[0]);
                else if(count($keys)>1 && is_string($keys[0])) Arr::set($store, $keys[0], $keys[1]);
                $this->header = $store;
                return $this;
            },
            'input'=>function(...$keys){
                $store = $this->input??[];
                if(blank($keys)) return $store;
                else if(count($keys) == 1 && $keys[0] === true) return http_build_query($store,'','&');
                else if(count($keys) == 1&& is_string($keys[0])) return Arr::get($store, $keys[0]);
                else if(count($keys) == 1&& is_array($keys[0])) $store = array_merge($store, $keys[0]);
                else if(count($keys)>1 && is_string($keys[0])) Arr::set($store, $keys[0], $keys[1]);
                $this->input = $store;
                return $this;
            },
            'response'=>function(...$keys){
                $store = $this->response??[];
                if(count($keys) && !is_string($keys[0]) && is_callable($keys[0]))
                    $keys[0] = Closure::call($keys[0], $this, $this);
                if(blank($keys)) return $store;
                else if(is_array($keys[0])) $store = array_merge($store, $keys[0]);
                else $store = $keys[0];
                $this->response = $store;
                return $this;
            },
            "url"=>function($url=null, $params=[]){
                if($url===true) return $this->url.($this->method=='GET'?'?'.$this->input(true):'');
                else if($url===null) return $this->url;
                foreach($params as $k=>$v){
                    if(preg_match("/\{$k\}/", $url)){
                        $url = str_replace('{'.$k.'}',$v, $url);
                        unset($params[$k]);
                    }
                }
                $url = explode(',',preg_replace("/^([^#?]+)?(\?[^#]+)?(\#.*)?$/","$1,$2,$3",\preg_replace("/\{[a-zA-Z-.]+\}/","",$url)));
                parse_str(preg_replace("/^\?(.*)/","$1",$url[1]),$inputs);
                return $this
                    ->curl_header('CURLOPT_URL', $url[0])
                    ->set("url",$url[0])
                    ->input($inputs)
                    ->input($params);
            },
            "method"=>function(...$method){ return blank($method)?\strtoupper($this->method):$this->set("method",\strtoupper($method[0])); },
            "cache"=>function($allow=true,...$time){
                $time = blank($time)?(is_string($allow)?$allow:180):$time[0];
                return $this->set('cache',[
                    'allow'=>boolval($allow),
                    'expire'=>is_string($time)?now()->add($time)->diffInSeconds():$time,
                ]);
            },
            '(GET)cacheKey'=>function(){ return md5($this->method.'_URL_CACHE_STORAGE_FOR_'.$this->url(true)); },
            '(GET)onCache'=>function(){
                return (
                    $this->cache['allow']
                    &&$this->method=='GET'
                    &&Cache::has($this->cacheKey)
                )?Cache::get($this->cacheKey):null;
            },
            'event'=>function($name,...$fn){
                if(blank($fn)) return ($this->events[$name]??null);
                $this->events[$name]=$fn[0];
                return $this;
            },

            'trigger'=>function($e,...$arg){ return $this->then($this->event($e),...$arg); },
            'before'=>function($fn){ return is_callable($fn)?$this->event('before',$fn):$this; },
            'success'=>function($fn){ return is_callable($fn)?$this->event('success',$fn):$this; },
            'error'=>function($fn){ return is_callable($fn)?$this->event('error',$fn):$this; },
            'allways'=>function($fn){ return is_callable($fn)?$this->event('allways',$fn):$this; },
            
            'send'=>function(...$fn){
                if(count($fn)) $this->success($fn[0])->error($fn[1]??null)->allways($fn[2]??null);
                $method = $this->method;
                $inputs = $this->input(true);
                
                $response = collection($this
                ->curl_header([
                    'CURLOPT_URL'=>$this->url(true),
                    'CURLOPT_RETURNTRANSFER'=>true,
                ])
                ->curl_header((($method=='POST')?["CURLOPT_POST"=>true,'CURLOPT_POSTFIELDS'=>$inputs,]:["CURLOPT_HTTPGET"=>true]))
                ->call($this->event('before'),$this,$this)
                ->response($this->onCache??function(){
                    $handler = curl_init();
                    // Curl Headers
                    $curl_header = [];
                    foreach($this->curl_header as $k => $v){
                        if(is_string($k)) $curl_header[constant($k)]=$v;
                        else array_push($curl_header, $v);
                    }
                    curl_setopt_array( $handler, $curl_header );
                    // Request Headers
                    $header = [];
                    foreach($this->header() as $k => $v){
                        if(is_string($k)) $header[$k]=$v;
                        else array_push($header, $v);
                    }
                    curl_setopt( $handler, CURLOPT_HTTPHEADER, $header );
                    // Execute
                    $data = curl_exec($handler);
                    $error = curl_error($handler);
                    $data = [
                        'status' => !$error,
                        'mime' => curl_getinfo($handler, CURLINFO_CONTENT_TYPE),
                        'code' => curl_getinfo($handler, CURLINFO_HTTP_CODE),
                        'error' => $error,
                        'body' => $data,
                    ];
                    curl_close($handler);
                    if ($data['status']
                        &&!$data['error']
                        &&$this->method=='GET'
                        &&$this->cache['allow']
                    ) Cache::put($this->cacheKey,$data,$this->cache['expire']);
                    return $data;
                })
                ->call($this->event('allways'),$this,$this)->response);
                $response['body'] = preg_match("/json$/",$response['mime'])?collection(json_decode($response['body'], true)):$response['body'];
                return $response['status']?Closure::call((
                    $this->event('success')??function($body, $data){return $data;}
                ), $this, $response['body'], $response):Closure::call((
                    $this->event('error')??function($body, $data){return $data;}
                ), $this, $response['error'], $response);
            },
        ];
    }


}
