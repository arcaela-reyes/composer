<?php
namespace Arcaela;
use Illuminate\Support\Str;


class LocalBitcoin extends Macroable{
	use Traits\Basics;

	public function Query($endPoint,$params=[]) {
		$endPoint = \preg_replace("/^(\/+)(.*)/","/$2",\preg_replace("/(.*)(\/+)$/","$1/",$endPoint));
		$this->API_AUTH_KEY = $this->publicKey;
		$this->API_AUTH_SECRET = $this->privateKey;
		if(!defined('SSL_VERIFYPEER')) define('SSL_VERIFYPEER',true);
		if(!defined('SSL_VERIFYHOST')) define('SSL_VERIFYHOST',true);
		// Method
		$api_get = ['/api/ads/','/api/ad-get/{ad_id}/','/api/ad-get/','/api/payment_methods/','/api/payment_methods/{countrycode}/','/api/countrycodes/','/api/currencies/','/api/places/','/api/contact_messages/{contact_id}/','/api/contact_info/{contact_id}/','/api/contact_info/','/api/account_info/{username}/','/api/dashboard/','/api/dashboard/released/','/api/dashboard/canceled/','/api/dashboard/closed/','/api/myself/','/api/notifications/','/api/real_name_verifiers/{username}/','/api/recent_messages/','/api/wallet/','/api/wallet-balance/','/api/wallet-addr/','/api/merchant/invoices/','/api/merchant/invoice/{invoice_id}/'];
		$api_post = ['/api/ad/{ad_id}/','/api/ad-create/','/api/ad-delete/{ad_id}/','/api/feedback/{username}/','/api/contact_release/{contact_id}/','/api/contact_release_pin/{contact_id}/','/api/contact_mark_as_paid/{contact_id}/','/api/contact_message_post/{contact_id}/','/api/contact_dispute/{contact_id}/','/api/contact_cancel/{contact_id}/','/api/contact_fund/{contact_id}','/api/contact_mark_realname/{contact_id}/','/api/contact_mark_identified/{contact_id}/','/api/contact_create/{ad_id}/','/api/logout/','/api/notifications/mark_as_read/{notification_id}/','/api/pincode/','/api/wallet-send/','/api/wallet-send-pin/','/api/merchant/new_invoice/','/api/merchant/delete_invoice/{invoice_id}/'];
		$api_public = ['/buy-bitcoins-with-cash/{location_id}/{location_slug}/.json','/sell-bitcoins-for-cash/{location_id}/{location_slug}/.json','/buy-bitcoins-online/{countrycode}/{country_name}/{payment_method}/.json','/buy-bitcoins-online/{countrycode}/{country_name}/.json','/buy-bitcoins-online/{currency}/{payment_method}/.json','/buy-bitcoins-online/{currency}/.json','/buy-bitcoins-online/{payment_method}/.json','/buy-bitcoins-online/.json','/sell-bitcoins-online/{countrycode}/{country_name}/{payment_method}/.json','/sell-bitcoins-online/{countrycode}/{country_name}/.json','/sell-bitcoins-online/{currency}/{payment_method}/.json','/sell-bitcoins-online/{currency}/.json','/sell-bitcoins-online/{payment_method}/.json','/sell-bitcoins-online/.json','/bitcoinaverage/ticker-all-currencies/','/bitcoincharts/{currency}/trades.json','/bitcoincharts/{currency}/orderbook.json'];
		$is_public = in_array($endPoint, $api_public);
		$method = in_array($endPoint, $api_post)?'post':'get';
		// Build NONCE
		$mt = explode(' ', microtime());
		$API_AUTH_NONCE = $mt[1].substr($mt[0], 2, 6);
		$Ajax = Ajax::url('http://localbitcoins.com'.$endPoint, $params)
		->curl_header([
			"CURLOPT_USERAGENT"=>'Mozilla/4.0 (compatible; LocalBitcoins API PHP client; '.php_uname('s').'; PHP/'.phpversion().')',
			...((SSL_VERIFYPEER!==true)?["CURLOPT_SSL_VERIFYPEER"=>false]:[]),
			...((SSL_VERIFYHOST!==true)?["CURLOPT_SSL_VERIFYHOST"=>false]:[]),
		])
		->method($method);
		$url = $Ajax->url(true);
		$datas = $Ajax->input(true);
		// Add Auth
		if(!$is_public) {
			$API_AUTH_SIGNATURE = strtoupper(hash_hmac('sha256', $API_AUTH_NONCE.($this->API_AUTH_KEY).$url.$datas, ($this->API_AUTH_SECRET)));
			$Ajax->header([
				'Apiauth-Key:'.($this->API_AUTH_KEY),
				'Apiauth-Nonce:'.$API_AUTH_NONCE,
				'Apiauth-Signature:'.$API_AUTH_SIGNATURE
			]);
		}
		return $Ajax->send(function($response){
			return collection(["error"=>null,"data"=>$response]);
		},function($e){
			return collection(["error"=>$e,"data"=>null]);
		});
	}


	public function lib(){
		return [
			'publicKey'=>function(...$key){ return $this->set('publicKey',...$key); },
			'privateKey'=>function(...$key){ return $this->set('privateKey',...$key); },

			'(GET)user'=>function(){ return $this->Query('/api/myself/')['data']??[]; },
			'(GET)wallet'=>function(){return $this->Query('/api/wallet-balance/')['data']??["total"=>0];},
			'(GET)balance'=>function(){return ($this->wallet['total']??['sendable'=>0])['sendable'];},
			'(GET)account'=>function(){
				return array_merge(
					$this->user,
					['wallet'=>$this->wallet]
				);
			},
			/* Anouncements (ISO)Currency (String)Action (Int)Page (Int)TimeExpire (array)ExcludeWords */
			"ads"=>function(...$arguments){
				$response=collection([
					"prev"=>null,
					"next"=>null,
					"ads"=>[],
					"error"=>null,
					'currency'=>'ves',
					'action'=>'buy',
					'page'=>1,
					'excludeTime'=>180,
					'excludeWords'=>[],
				])->merge(blank($arguments)?request()->all():(
					array_combine(array_slice(['currency','action','page','excludeTime','excludeWords'],0,count($arguments)),$arguments)
				));
				$result = $this->Query("/{action}-bitcoins-online/{currency}/.json",$response->only('action','currency','page')->toArray());
				if($result->error) return $result;
				return $response->merge([
					"prev"=>preg_replace("/.*page=(\d+).*$/","$1",$result->data['pagination']['prev']??''),
					"next"=>preg_replace("/.*page=(\d+).*$/","$1",$result->data['pagination']['next']??''),
					'ads'=>array_values(array_filter(array_map(function($ad) use($response){
						return collection(array_merge($ad['data'],[
							'link'=>"https://localbitcoins.com/ad/{$ad['data']['ad_id']}",
							'page'=>$response->page,
							"price" => floatval($ad['data']['temp_price']),
							'min_amount'=>!is_numeric($ad['data']['min_amount'])?0:floatval($ad['data']['min_amount']),
							'max_amount'=>floatval(is_numeric($ad['data']['max_amount'])?(
								$ad['data']['max_amount']>$ad['data']['max_amount_available']?$ad['data']['max_amount_available']:$ad['data']['max_amount']
							):(is_numeric($ad['data']['max_amount_available'])?$ad['data']['max_amount_available']:9000000000000000000)),
							"bank_filter" => strtolower(preg_replace("(\W+)","", $ad['data']['online_provider'].$ad['data']['bank_name'])),
							"last_seen" => strtotime($ad['data']['profile']['last_online'],false),
							"last_seen_minutes" => round((strtotime('now')-strtotime($ad['data']['profile']['last_online']))/60),
							"limits"=>array_filter(explode(',',$ad['data']['limit_to_fiat_amounts']??'')),
						]));
					},$result->data->data['ad_list']),function($item)use($response){
						return true
							&&$item['visible']
							&&$response->excludeTime>=$item['last_seen_minutes']
							&&!Str::contains($item['bank_filter'], $response->excludeWords);
					})),
				])
				->only('prev','page','next','error','action','ads');
			},
		];
	}

}