<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$transaction_types = array(
    'P' => 'AUTH_CAPTURE',
    'A' => 'AUTH_ONLY',
    'C' => 'CAPTURE_ONLY',
    'R' => 'CREDIT',
    'I' => 'PRIOR_AUTH_CAPTURE'
);

$trans_type = $processor_data['processor_params']['transaction_type'];
$__version = '3.1';
$post = array();

if ($trans_type == 'R') {
    $post['x_trans_id'] = $order_info['payment_info']['transaction_id'];
}

$processor_error = array();

$processor_error['avs'] = array(
    'A' => 'Address (Street) matches, ZIP does not',
    'B' => 'Address information not provided for AVS check',
    'D' => 'Exact AVS Match',
    'E' => 'AVS error',
    'F' => 'Exact AVS Match',
    'G' => 'Service not supported by issuer',
    'M' => 'Address (Street) matches',
    'N' => 'No Match on Address (Street) or ZIP',
    'P' => 'ZIP/Postal code matches, Address (Street) does not',
    'R' => 'Retry. System unavailable or timed out',
    'S' => 'Service not supported by issuer',
    'U' => 'Address information is unavailable',
    'W' => '9 digit ZIP matches, Address (Street) does not',
    'X' => 'Exact AVS Match',
    'Y' => 'Address (Street) and 5 digit ZIP match',
    'Z' => '5 digit ZIP matches, Address (Street) does not',
    '1' => 'Exact AVS Match',
    '2' => 'Exact AVS Match',
    '3' => 'Address (Street) matches, ZIP does not'
);

$processor_error['cvv'] = array(
    'M' => 'Match',
    'N' => 'CVV2 code: No Match',
    'P' => 'CVV2 code: Not Processed',
    'S' => 'CVV2 code: Should have been present',
    'U' => 'CVV2 code: Issuer unable to process request'
);

$processor_error['cavv'] = array(
    '0' => 'CAVV not validated because erroneous data was submitted',
    '1' => 'CAVV failed validation',
    '2' => 'CAVV passed validation',
    '3' => 'CAVV validation could not be performed; issuer attempt incomplete',
    '4' => 'CAVV validation could not be performed; issuer system error',
    '7' => 'CAVV attempt - failed validation - issuer available (US issued card/non-US acquirer)',
    '8' => 'CAVV attempt - passed validation - issuer available (US issued card/non-US acquirer)',
    '9' => 'CAVV attempt - failed validation - issuer unavailable (US issued card/non-US acquirer)',
    'A' => 'CAVV attempt - passed validation - issuer unavailable (US issued card/non-US acquirer)',
    'B' => 'CAVV passed validation, information only, no liability shift'
);

$processor_error['order_status'] = array(
    '1' => 'P',
    '2' => 'D',
    '3' => 'F',
    '4' => 'O' // Transaction is held for review...
);

$tran_error = array(
    '0' => "Transaction Successful",
    '100' => 'No matching transaction',
    '101' => 'A void operation cannot be performed because the original transaction has already been voided, credited, or settled.',
    '102' => 'A credit operation cannot be performed because the original transaction has already been voided, credited, or has not been settled.',
    '103' => 'A ticket operation cannot be performed because the original auth-only transaction has been voided or ticketed.',
    '104' => 'The bank has declined the transaction.',
    '105' => 'The bank has declined the transaction because the account is over limit.',
    '106' => 'The transaction was declined because the security code (CVV) supplied was invalid.',
    '107' => 'The bank has declined the transaction because the card is expired.',
    '108' => 'The bank has declined the transaction and has requested that the merchant call.',
    '109' => 'The bank has declined the transaction and has requested that the merchant pickup the card.',
    '110' => 'The bank has declined the transaction due to excessive use of the card.',
    '111' => 'The bank has indicated that the account is invalid.',
    '112' => 'The bank has indicated that the account is expired.',
    '113' => 'The issuing bank is temporarily unavailable. May be tried again later.',
    '117' => 'The transaction was declined because the address could not be verified.',
    '150' => 'The transaction was declined because the address could not be verified.',
    '151' => 'The transaction was declined because the security code (CVV) supplied was invalid.',
    '152' => 'The TICKET request was for an invalid amount. Please verify the TICKET for less then the AUTH_ONLY.',
    '200' => 'Transaction was declined', 	# Risk Fail
    '201' => 'Transaction was declined',	# Customer blocked
    '300' => 'A DNS failure has prevented the merchant application from resolving gateway host names.',
    '301' => 'The merchant application is unable to connect to an appropriate host.',
    '303' => 'A timeout occurred while waiting for a transaction response from the gateway servers.',
    '305' => 'Service Unavailable',
    '307' => 'Unexpected/Internal Error',
    '311' => 'Bank Communications Error',
    '312' => 'Bank Communications Error',
    '313' => 'Bank Communications Error',
    '314' => 'Bank Communications Error',
    '315' => 'Bank Communications Error',
    '400' => 'Invalid XML',
    '402' => 'Invalid Transaction',
    '403' => 'Invalid Card Number',
    '404' => 'Invalid Expiration',
    '405' => 'Invalid Amount',
    '406' => 'Invalid Merchant ID',
    '407' => 'Invalid Merchant Account',
    '408' => 'The merchant account specified in the request is not setup to accept the card type included in the request.',
    '409' => 'No Suitable Account',
    '410' => 'Invalid Transact ID',
    '411' => 'Invalid Access Code',
    '412' => 'Invalid Customer Data Length',
    '413' => 'Invalid External Data Length',
    '418' => 'Invalid Currency',
    '419' => 'Incompatible Currency',
    '420' => 'Invalid Rebill Arguments',
    '421' => 'Invalid Phone',
    '436' => 'Incompatible Descriptors',
    '438' => 'Invalid Site ID',
    '443' => 'Transaction Declined, Invalid Request. Please contact support',
    '444' => 'Transaction Declined, Invalid Request. Please contact support',
    '445' => 'Transaction Declined, Invalid Request. Please contact support',
    '446' => 'Transaction Declined, Invalid Request. Please contact support'
);
$invoice_no = $processor_data['processor_params']['order_prefix'] . (($order_info['repaid']) ? ($order_id . '_' . $order_info['repaid']) : $order_id);

require_once 'rocketgate_files/GatewayService.php';

$request 	= new GatewayRequest();
$response	= new GatewayResponse();
$service 	= new GatewayService();

$request->Set(GatewayRequest::MERCHANT_ID(), $processor_data['processor_params']['login']);
$request->Set(GatewayRequest::MERCHANT_PASSWORD(), $processor_data['processor_params']['transaction_key']);

if (!empty(Tygh::$app['session']['auth']['user_id'])) {
    $request->Set(GatewayRequest::MERCHANT_CUSTOMER_ID(), Tygh::$app['session']['auth']['user_id']);
}

$request->Set(GatewayRequest::MERCHANT_INVOICE_ID(), $invoice_no );

// BEGIN Risk Management
$request->Set(GatewayRequest::SCRUB(), $processor_data['processor_params']['scrubmode']);
$request->Set(GatewayRequest::CVV2_CHECK(), 'true');
$request->Set(GatewayRequest::AVS_CHECK(), $processor_data['processor_params']['avsmode']);
// END Risk Management

// Pass requested payment info.
$request->Set(GatewayRequest::CARDNO(), $order_info['payment_info']['card_number']);
$request->Set(GatewayRequest::EXPIRE_MONTH(), $order_info['payment_info']['expiry_month']);
$request->Set(GatewayRequest::EXPIRE_YEAR(), $order_info['payment_info']['expiry_year']);
$request->Set(GatewayRequest::CVV2(), $order_info['payment_info']['cvv2']);

$request->Set(GatewayRequest::AMOUNT(), fn_format_price($order_info['total']));
$request->Set(GatewayRequest::CURRENCY(), $processor_data['processor_params']['currency']);
// Billing address
if (!empty($order_info['b_firstname'])) {
    $request->Set(GatewayRequest::CUSTOMER_FIRSTNAME(), $order_info['b_firstname']);
}
if (!empty($order_info['b_lastname'])) {
    $request->Set(GatewayRequest::CUSTOMER_LASTNAME(), $order_info['b_lastname']);
}
if (!empty($order_info['phone'])) {
    $request->Set(GatewayRequest::CUSTOMER_PHONE_NO(), $order_info['phone']);
}
if (!empty($order_info['b_address'])) {
    $request->Set(GatewayRequest::BILLING_ADDRESS(), $order_info['b_address']);
}
if (!empty($order_info['b_city'])) {
    $request->Set(GatewayRequest::BILLING_CITY(), $order_info['b_city']);
}
if (!empty($order_info['b_state'])) {
    $request->Set(GatewayRequest::BILLING_STATE(), $order_info['b_state']);
}
if (!empty($order_info['b_zipcode'])) {
    $request->Set(GatewayRequest::BILLING_ZIPCODE(), $order_info['b_zipcode']);
}
if (!empty($order_info['b_country'])) {
    $request->Set(GatewayRequest::BILLING_COUNTRY(), $order_info['b_country']);
}
if (!empty($order_info['email'])) {
    $request->Set(GatewayRequest::EMAIL(), $order_info['email']);
}

if ($processor_data['processor_params']['mode'] == 'test') {
    $service->SetTestMode(TRUE);
}

if ($transaction_types[$trans_type] == 'AUTH_CAPTURE') {
    $service_response = $service->PerformPurchase($request, $response);
    $transaction_type = 'sale';
} elseif ($transaction_types[$trans_type] == 'AUTH_ONLY') {
    $service_response = $service->PerformAuthOnly($request, $response);
    $transaction_type = 'auth';
}
// Gateway answered
$pp_response = array();
if ($response->Get(GatewayResponse::RESPONSE_CODE()) == GatewayCodes__RESPONSE_SUCCESS) {
    // check CVV2 response
    $cvv_code = $response->Get(GatewayResponse::CVV2_CODE());
    switch ($cvv_code) {
        case 'N':
        case 'P':
        case 'S':
        case 'U':
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = $processor_error['cvv'][$cvv_code];
            break;
        default:
            $pp_response['order_status'] = 'P';
            $pp_response['reason_text'] = '';
            break;
    }
} else {
    $pp_response['order_status'] = 'F';
    $pp_response['reason_text'] = $tran_error[$response->Get(GatewayResponse::REASON_CODE())];
}
$pp_response['transaction_id'] = $response->Get(GatewayResponse::TRANSACT_ID());
