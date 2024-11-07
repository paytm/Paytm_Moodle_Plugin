<?php

class PaytmConstants{
	CONST INITIATE_TRANSACTION_URL		        = "theia/api/v1/initiateTransaction";
	
	CONST PRODUCTION_HOST						= "https://secure.paytmpayments.com/";
	CONST STAGING_HOST							= "https://securestage.paytmpayments.com/";

	CONST ORDER_STATUS_URL		                = "v3/order/status";
	CONST CHECKOUT_JS_URL						= "merchantpgpui/checkoutjs/merchants/MID.js";


	CONST SAVE_PAYTM_RESPONSE 					= true;
	CONST CHANNEL_ID							= "WEB";
	CONST APPEND_TIMESTAMP						= false;
	CONST X_REQUEST_ID							= "MOODLE_";
	CONST PLUGIN_DOC_URL						= "https://developer.paytm.com/";

	CONST MAX_RETRY_COUNT						= 3;
	CONST CONNECT_TIMEOUT						= 10;
	CONST TIMEOUT								= 10;

	CONST CUSTOM_CALLBACK_URL					= "";
	CONST CURRENCY_MIS_MATCH					= "Currency does not match course settings, received: ";
	CONST NOT_VALID_USER_ID				    	= "Not a valid user id \n";
	CONST NOT_VALID_COURSE_ID					= "Not a valid course id \n";
	CONST NOT_VALID_CONTEXT_ID					= "Not a valid context id \n";
	CONST NOT_VALID_INSTANCE_ID					= "Not a valid instance id \n";
	CONST IMPROPER_MERCHANT_CONFIG				= "Merchent configurations are not done properly. Please contact paytm integration 
	 support.";
	CONST SERVER_COMM_ERROR                     = "It seems some issue in server to server communication. Kindly connect with administrator.";


	CONST ID									= "paytm";
	CONST METHOD_TITLE							= "Paytm Payments";
	CONST METHOD_DESCRIPTION					= "The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.";

	CONST TITLE									= "Paytm";
	CONST DESCRIPTION							= "The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.";

	 
	
	CONST FRONT_MESSAGE						= "Thank you for your order, please click the button below to pay with paytm.";
	CONST NOT_FOUND_TXN_URL					= "Something went wrong. Kindly contact with us.";
	CONST PAYTM_PAY_BUTTON					= "Pay via Paytm";
	CONST CANCEL_ORDER_BUTTON				= "Cancel order & Restore cart";
	CONST POPUP_LOADER_TEXT					= "Thank you for your order. We are now redirecting you to paytm to make payment.";

	CONST TRANSACTION_ID					= "<b>Transaction ID:</b> %s";
	CONST PAYTM_ORDER_ID					= "<b>Paytm Order ID:</b> %s";

	CONST REASON							= " Reason: %s";
	CONST FETCH_BUTTON						= "Fetch Status";

	//Success
	CONST SUCCESS_ORDER_MESSAGE				= "Thank you for your order. Your payment has been successfully received.";
	CONST SUCCESS_TXN_TOKEN				    = "Transaction token generated successfully";
	CONST RESPONSE_SUCCESS					= "Updated <b>STATUS</b> has been fetched";
	CONST RESPONSE_STATUS_SUCCESS			= " and Transaction Status has been updated <b>PENDING</b> to <b>%s</b>";
	CONST RESPONSE_ERROR					= "Something went wrong. Please try again";

	//Error
	CONST PENDING_ORDER_MESSAGE				= "Your payment has been pending!";
	CONST ERROR_ORDER_MESSAGE				= "Your payment has been failed!";
	CONST ERROR_SERVER_COMMUNICATION		= "It seems some issue in server to server communication. Kindly connect with us.";
	CONST ERROR_CHECKSUM_MISMATCH			= "Security Error. Checksum Mismatched!";
	CONST ERROR_AMOUNT_MISMATCH				= "Security Error. Amount Mismatched!";
	CONST ERROR_INVALID_ORDER				= "No order found to process. Kindly contact with us.";
	CONST ERROR_CURL_DISABLED				= "cURL is not enabled properly. Please verify.";
	CONST ERROR_CURL_WARNING				= "Your server is unable to connect with us. Please contact to Paytm Support.";

	CONST PLUGIN_VERSION						= "2.1";



}

?>