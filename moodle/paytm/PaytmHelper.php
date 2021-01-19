<?php
require_once(__DIR__.'/PaytmConstants.php');
if(!class_exists('PaytmHelper')) :
class PaytmHelper{

	/**
	* include timestap with order id
	*/
	public static function getPaytmOrderId($order_id){
		if($order_id && PaytmConstants::APPEND_TIMESTAMP){
			return $order_id . '_' . date("YmdHis");
		}else{
			return $order_id;
		}
	}
	/**
	* exclude timestap with order id
	*/
	public static function getOrderId($order_id){		
		if(($pos = strrpos($order_id, '_')) !== false && PaytmConstants::APPEND_TIMESTAMP) {
			$order_id = substr($order_id, 0, $pos);
		}
		return $order_id;
	}

	public static function getPaytmURL($url = false, $isProduction = 0){
		if(!$url) return false; 
		if($isProduction == 1){
			return PaytmConstants::PRODUCTION_HOST . $url;
		}else{
			return PaytmConstants::STAGING_HOST . $url;			
		}
	}

	public static function getPaytmHostURL($isProduction = 0){
		if($isProduction == 1){
			return PaytmConstants::PRODUCTION_HOST;
		}else{
			return PaytmConstants::STAGING_HOST;			
		}
	}
	/**
	* exclude timestap with order id
	*/
	public static function getTransactionStatusURL($isProduction = 0){		
		if($isProduction == 1){
			return PaytmConstants::TRANSACTION_STATUS_URL_PRODUCTION;
		}else{
			return PaytmConstants::TRANSACTION_STATUS_URL_STAGING;			
		}
	}
	/**
	* check and test cURL is working or able to communicate properly with paytm
	*/
	public static function validateCurl($transaction_status_url = ''){		
		if(!empty($transaction_status_url) && function_exists("curl_init")){
			$ch 	= curl_init(trim($transaction_status_url));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			$res 	= curl_exec($ch);
			curl_close($ch);
			return $res !== false;
		}
		return false;
	}

	public static function getcURLversion(){		
		if(function_exists('curl_version')){
			$curl_version = curl_version();
			if(!empty($curl_version['version'])){
				return $curl_version['version'];
			}
		}
		return false;
	}

	public static function executecUrl($apiURL, $requestParamList) {
		
		$postData = json_encode($requestParamList, JSON_UNESCAPED_SLASHES);
		$ch = curl_init($apiURL);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, PaytmConstants::CONNECT_TIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, PaytmConstants::TIMEOUT);
		
		/*
		** default value is 2 and we also want to use 2
		** so no need to specify since older PHP version might not support 2 as valid value
		** see https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html
		*/
		// curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);

		// TLS 1.2 or above required
		// curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json', 
			'Content-Length: ' . strlen($postData))
		);
		$jsonResponse = curl_exec($ch);   

		if (!curl_errno($ch)) {
			return json_decode($jsonResponse, true);
		} else {
			return false;
		}
	}

}
endif;
?>