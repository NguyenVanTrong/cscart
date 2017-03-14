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
require_once(dirname(__FILE__)."/GatewayCodes.php");
require_once(dirname(__FILE__)."/GatewayParameterList.php");

////////////////////////////////////////////////////////////////////////////////
//
//	GatewayResponse() - Object that holds name-value pairs
//			    that describe a gateway response.
//
////////////////////////////////////////////////////////////////////////////////
//
class GatewayResponse extends GatewayParameterList
{
  /**
   * GatewayResponse constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->GatewayResponse();
  }
//////////////////////////////////////////////////////////////////////
//
//	GatewayResponse() - Constructor for class.
//
//////////////////////////////////////////////////////////////////////
//
  public function GatewayResponse()
  {
//
//	Initialize the parameter list.
//
  }

//////////////////////////////////////////////////////////////////////
//
//	SetResults() - Set the response and reason values.
//
//////////////////////////////////////////////////////////////////////
//
  public function SetResults($response, $reason)
  {
    $this->Set(GatewayResponse::RESPONSE_CODE(), $response);
    $this->Set(GatewayResponse::REASON_CODE(), $reason);
  }

//////////////////////////////////////////////////////////////////////
//
//	SetFromXML() - Set the internal parameters using
//		       the contents of an XML document.
//
//////////////////////////////////////////////////////////////////////
//
  public function SetFromXML($xmlString)
  {

//
//	Create a parser for the XML.
//
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);

//
//	Parse the input string.  If there is an error,
//	note it in the response.
//
    if (xml_parse_into_struct($parser, $xmlString, $vals, $index) == 0) {
      $this->Set(GatewayResponse::EXCEPTION(),
         xml_error_string(xml_get_error_code($parser)));
      $this->SetResults(GatewayCodes__RESPONSE_SYSTEM_ERROR,
            GatewayCodes__REASON_XML_ERROR);
      xml_parser_free($parser);			// Release the parser

      return;					// And we're done
    }

//
//	Loop over the items in the XML document and
//	save them in the response.
//
    foreach ($vals as $val) {			// Loop over elements
      if (isset($val['value']))			// Is value set?
        $this->Set($val['tag'], $val['value']);	// Save in parameters
    }

//
//	Release the parser and quit.
//
    xml_parser_free($parser);			// Release the parser
  }

//////////////////////////////////////////////////////////////////////
//
//	Functions that provide constants for name-value pairs.
//
//////////////////////////////////////////////////////////////////////
//
  public static function VERSION_INDICATOR() { return "version"; }
  public static function ACS_URL() { return "acsURL"; }
  public static function AUTH_NO() { return "authNo"; }
  public static function AVS_RESPONSE() { return "avsResponse"; }
  public static function BALANCE_AMOUNT() { return "balanceAmount"; }
  public static function BALANCE_CURRENCY() { return "balanceCurrency"; }
  public static function BANK_RESPONSE_CODE() { return "bankResponseCode"; }
  public static function CARD_TYPE() { return "cardType"; }
  public static function CARD_HASH() { return "cardHash"; }
  public static function CARD_LAST_FOUR() { return "cardLastFour"; }
  public static function CARD_EXPIRATION() { return "cardExpiration"; }
  public static function CARD_COUNTRY() { return "cardCountry"; }
  public static function CARD_REGION() { return "cardRegion"; }
  public static function CARD_DESCRIPTION() { return "cardDescription"; }
  public static function CARD_DEBIT_CREDIT() { return "cardDebitCredit"; }
  public static function CARD_ISSUER_NAME() { return "cardIssuerName"; }
  public static function CARD_ISSUER_PHONE() { return "cardIssuerPhone"; }
  public static function CARD_ISSUER_URL() { return "cardIssuerURL"; }
  public static function CVV2_CODE() { return "cvv2Code"; }
  public static function EXCEPTION() { return "exception"; }
  public static function JOIN_DATE() { return "joinDate"; }
  public static function JOIN_AMOUNT() { return "joinAmount"; }
  public static function LAST_BILLING_DATE() { return "lastBillingDate"; }
  public static function LAST_BILLING_AMOUNT() { return "lastBillingAmount"; }
  public static function LAST_REASON_CODE() { return "lastReasonCode"; }
  public static function MERCHANT_ACCOUNT() { return "merchantAccount"; }
  public static function MERCHANT_CUSTOMER_ID() { return "merchantCustomerID"; }
  public static function MERCHANT_INVOICE_ID() { return "merchantInvoiceID"; }
  public static function MERCHANT_PRODUCT_ID() { return "merchantProductID"; }
  public static function MERCHANT_SITE_ID() { return "merchantSiteID"; }
  public static function PAREQ() { return "PAREQ"; }
  public static function REASON_CODE() { return "reasonCode"; }
  public static function REBILL_AMOUNT() { return "rebillAmount"; }
  public static function REBILL_DATE() { return "rebillDate"; }
  public static function REBILL_END_DATE() { return "rebillEndDate"; }
  public static function REBILL_FREQUENCY() { return "rebillFrequency"; }
  public static function REBILL_STATUS() { return "rebillStatus"; }
  public static function RESPONSE_CODE() { return "responseCode"; }
  public static function TRANSACT_ID() { return "guidNo"; }
  public static function SCRUB_RESULTS() { return "scrubResults"; }
  public static function SETTLED_AMOUNT() { return "approvedAmount"; }
  public static function SETTLED_CURRENCY() { return "approvedCurrency"; }
  public static function RETRIEVAL_NO() { return "retrievalNo"; }
}
