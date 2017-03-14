<?php
/*
 * Copyright notice:
 * (c) Copyright 2007-2014 RocketGate LLC
 * All rights reserved.
 *
 * The copyright notice must not be removed without specific, prior
 * written permission from RocketGate LLC.
 *
 * This software is protected as an unpublished work under the U.S. copyright
 * laws. The above copyright notice is not intended to effect a publication of
 * this work.
 * This software is the confidential and proprietary information of RocketGate LLC.
 * Neither the binaries nor the source code may be redistributed without prior
 * written permission from RocketGate LLC.
 *
 * The software is provided "as-is" and without warranty of any kind, express, implied
 * or otherwise, including without limitation, any warranty of merchantability or fitness
 * for a particular purpose.  In no event shall RocketGate LLC be liable for any direct,
 * special, incidental, indirect, consequential or other damages of any kind, or any damages
 * whatsoever arising out of or in connection with the use or performance of this software,
 * including, without limitation, damages resulting from loss of use, data or profits, and
 * whether or not advised of the possibility of damage, regardless of the theory of liability.
 *
 */
require_once(dirname(__FILE__)."/GatewayParameterList.php");
require_once(dirname(__FILE__)."/GatewayChecksum.php");

////////////////////////////////////////////////////////////////////////////////
//
//	GatewayRequest() - Object that holds name-value pairs
//			   that describe a gateway request.
//
////////////////////////////////////////////////////////////////////////////////
//
class GatewayRequest extends GatewayParameterList
{
  public function __construct()
  {
    parent::__construct();
    $this->GatewayRequest();
  }
//////////////////////////////////////////////////////////////////////
//
//	GatewayRequest() - Constructor for class.
//
//////////////////////////////////////////////////////////////////////
//
  public function GatewayRequest()
  {
//
//	Initialize the request list.
//
    $this->Set(GatewayRequest::VERSION_INDICATOR(),
           GatewayChecksum::$versionNo);
  }

//////////////////////////////////////////////////////////////////////
//
//	ToXMLString() - Transform the parameter list into
//			an XML String.
//
//////////////////////////////////////////////////////////////////////
//
  public function ToXMLString()
  {

//
//	Build the header of XML document.
//
    $xmlString = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
         "<gatewayRequest>";

//
//	Loop over the list of values in the parameter list.
//
    foreach ($this->params as $key => $value) {
      $xmlString .= "<" . $key . ">";		// Add opening of element
      $xmlString .= $this->TranslateXML($value);
      $xmlString .= "</" . $key . ">";		// Add closing of element
    }

//
//	Put the closing marker on the XML document and quit.
//
    $xmlString .= "</gatewayRequest>";		// Add the terminator

    return $xmlString;				// Return completed XML
  }

//////////////////////////////////////////////////////////////////////
//
//	TranslateXML() - Translate a string to a valid XML
//			 string that can be used in an attribute
//			 or text node.
//
//////////////////////////////////////////////////////////////////////
//
  public function TranslateXML($sourceString)
  {
    $sourceString = str_replace("&", "&amp;", $sourceString);
    $sourceString = str_replace("<", "&lt;", $sourceString);
    $sourceString = str_replace(">", "&gt;", $sourceString);

    return $sourceString;			// Give back results
  }

//////////////////////////////////////////////////////////////////////
//
//	Functions that provide constants for name-value pairs.
//
//////////////////////////////////////////////////////////////////////
//
  public static function VERSION_INDICATOR() { return "version"; }
  public static function ACCOUNT_HOLDER() { return "accountHolder"; }
  public static function ACCOUNT_NO() { return "accountNo"; }
  public static function AFFILIATE() { return "affiliate"; }
  public static function AMOUNT() { return "amount"; }
  public static function AVS_CHECK() { return "avsCheck"; }
  public static function BILLING_ADDRESS() { return "billingAddress"; }
  public static function BILLING_CITY() { return "billingCity"; }
  public static function BILLING_COUNTRY() { return "billingCountry"; }
  public static function BILLING_STATE() { return "billingState"; }
  public static function BILLING_TYPE() { return "billingType"; }
  public static function BILLING_ZIPCODE() { return "billingZipCode"; }
  public static function CARDNO() { return "cardNo"; }
  public static function CARD_HASH() { return "cardHash"; }
  public static function CLONE_CUSTOMER_RECORD() { return "cloneCustomerRecord"; }
  public static function CLONE_TO_CUSTOMER_ID() { return "cloneToCustomerID"; }
  public static function CURRENCY() { return "currency"; }
  public static function CUSTOMER_FIRSTNAME() { return "customerFirstName"; }
  public static function CUSTOMER_LASTNAME() { return "customerLastName"; }
  public static function CUSTOMER_PASSWORD() { return "customerPassword"; }
  public static function CUSTOMER_PHONE_NO() { return "customerPhoneNo"; }
  public static function CVV2() { return "cvv2"; }
  public static function CVV2_CHECK() { return "cvv2Check"; }
  public static function EMAIL() { return "email"; }
  public static function EXPIRE_MONTH() { return "expireMonth"; }
  public static function EXPIRE_YEAR() { return "expireYear"; }
  public static function GENERATE_POSTBACK() { return "generatePostback"; }
  public static function IOVATION_BLACK_BOX() { return "iovationBlackBox"; }
  public static function IOVATION_RULE() { return "iovationRule"; }
  public static function IPADDRESS() { return "ipAddress"; }
  public static function MERCHANT_ACCOUNT() { return "merchantAccount"; }
  public static function MERCHANT_CUSTOMER_ID() { return "merchantCustomerID"; }
  public static function MERCHANT_DESCRIPTOR() { return "merchantDescriptor"; }
  public static function MERCHANT_INVOICE_ID() { return "merchantInvoiceID"; }
  public static function MERCHANT_ID() { return "merchantID"; }
  public static function MERCHANT_PASSWORD() { return "merchantPassword"; }
  public static function MERCHANT_PRODUCT_ID() { return "merchantProductID"; }
  public static function MERCHANT_SITE_ID() { return "merchantSiteID"; }
  public static function OMIT_RECEIPT() { return "omitReceipt"; }
  public static function PARES() { return "PARES"; }
  public static function REBILL_FREQUENCY() { return "rebillFrequency"; }
  public static function REBILL_AMOUNT() { return "rebillAmount"; }
  public static function REBILL_START() { return "rebillStart"; }
  public static function REBILL_END_DATE() { return "rebillEndDate"; }
  public static function REBILL_COUNT() { return "rebillCount"; }
  public static function REBILL_SUSPEND() { return "rebillSuspend"; }
  public static function REBILL_RESUME() { return "rebillResume"; }
  public static function REFERENCE_GUID() { return "referenceGUID"; }
  public static function REFERRAL_NO() { return "referralNo"; }
  public static function REFERRING_MERCHANT_ID() { return "referringMerchantID"; }
  public static function REFERRED_CUSTOMER_ID() { return "referredCustomerID"; }
  public static function ROUTING_NO() { return "routingNo"; }
  public static function SAVINGS_ACCOUNT() { return "savingsAccount"; }
  public static function SCRUB() { return "scrub"; }
  public static function SS_NUMBER() { return "ssNumber"; }
  public static function TRANSACT_ID() { return GatewayRequest::REFERENCE_GUID(); }
  public static function TRANSACTION_TYPE() { return "transactionType"; }
  public static function UDF01() { return "udf01"; }
  public static function UDF02() { return "udf02"; }
  public static function USE_3D_SECURE() { return "use3DSecure"; }
  public static function USERNAME() { return "username"; }
  public static function FAILED_SERVER() { return "failedServer"; }
  public static function FAILED_GUID() { return "failedGUID"; }
  public static function FAILED_RESPONSE_CODE() { return "failedResponseCode"; }
  public static function FAILED_REASON_CODE() { return "failedReasonCode"; }

//////////////////////////////////////////////////////////////////////
//
//	Functions used to override gateway service URL.
//
//////////////////////////////////////////////////////////////////////
//
  public static function GATEWAY_SERVER() { return "gatewayServer"; }
  public static function GATEWAY_PROTOCOL() { return "gatewayProtocol"; }
  public static function GATEWAY_PORTNO() { return "gatewayPortNo"; }
  public static function GATEWAY_SERVLET() { return "gatewayServlet"; }
  public static function GATEWAY_CONNECT_TIMEOUT() { return "gatewayConnectTimeout"; }
  public static function GATEWAY_READ_TIMEOUT() { return "gatewayReadTimeout"; }
}
