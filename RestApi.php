<?php
namespace Arcaela;

use Illuminate\Support\Arr;
use Arcaela\Helper\Promises;

class RestApi extends Macroable{
   use Traits\Basics;

   public $content = [
      "header"=>[
         "code"=>200,
         "redirect"=>null,
      ],
      "response"=>[
         "status"=>"success",
         "data"=>null,
      ],
   ];
   public function booted(){
      if($next=request()->input('next'))
         $this->next($next);
   }
   public function lib(){
        return [
         'prop'=>function(...$keys){
            $store = $this->content??[];
            if(blank($keys)) return $store;
            if(count($keys)==1&&is_string($keys[0])) return Arr::get($store, $keys[0]);
            else if(count($keys) == 1&& is_array($keys[0])) $store = array_merge($store, $keys[0]);
            else if(count($keys)>1 && is_string($keys[0])) Arr::set($store, $keys[0], $keys[1]);
            $this->content = $store;
            return $this;
        },
			//////////////////////////////////////////////////
			'code'=>function($code=null){
				if(is_numeric($code)) $this->prop('headers.code',$code);
				else if($code==true) return $this->prop('headers.code');
				return $this;
         },
			'next'=>function($continue=null){
				if(is_string($continue)&&!empty($continue)) $this->prop('headers.redirect',$continue);
				else if($continue===false||is_null($continue)) $this->prop('headers.redirect',null);
				return $this;
         },
			'addresponse'=>function($response){
				return $this->prop('response.data',$response);
         },
         
			"send"=>function(){
				$statusCode = $this->prop('headers.code');
            $statusText =  $this->prop('headers.statusText')??(($statusCode>199&&$statusCode<300)?"success":($statusCode>399?"error":"unknowm"));
				return $this->loadItems([
               "status"=>$statusText,
               (($statusText=='success')?'data':'message')=>$this->prop('content'),
					"timestamp"=>strtotime("now"),
            ])->call(function(){
               $next = $this->prop('headers.redirect');
               !is_null($next)?$this->items("next", $next):null;
            });
         },
			//////////////////////////////////////////////////
			"message"=>function($data,$status='success'){
				if(blank($data)) return $this;
				return $this
					->prop("headers.statusText",$status)
					->prop("content",$data)
               ->send();
         },
			"onSuccess"=>function($done,$else){
            $success = is_string($done)?$done:(is_callable($done)?Closure::call($done,$this,$this):null);
				return !$success?
               $this->error(
                  is_string($else)?$else:(is_callable($else)?Closure::call($else,$this,$this):"Ocurrió un error inesperado")
               ):$this->success($success);
			},
			"success"=>function($data){return $this->message($data,"success");},
         "error"=>function($data){return $this->message($data,"error");},
		];
    }



}