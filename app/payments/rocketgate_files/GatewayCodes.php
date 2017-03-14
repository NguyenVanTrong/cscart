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

//////////////////////////////////////////////////////////////////////
//
//	Declaration of static response codes.
//
//////////////////////////////////////////////////////////////////////
//
define("GatewayCodes__RESPONSE_SUCCESS", 0);	// Function succeeded
define("GatewayCodes__RESPONSE_BANK_FAIL", 1);	// Bank decline/failure
define("GatewayCodes__RESPONSE_RISK_FAIL", 2);	// Risk failure
define("GatewayCodes__RESPONSE_SYSTEM_ERROR", 3);
                        // Server/recoverable error
define("GatewayCodes__RESPONSE_REQUEST_ERROR", 4);
                        // Invalid request

//////////////////////////////////////////////////////////////////////
//
//	Declaration of static reason codes.
//
//////////////////////////////////////////////////////////////////////
//
define("GatewayCodes__REASON_SUCCESS", 0);	// Function succeeded

define("GatewayCodes__REASON_NOMATCHING_XACT", 100);
define("GatewayCodes__REASON_CANNOT_VOID", 101);
define("GatewayCodes__REASON_CANNOT_CREDIT", 102);
define("GatewayCodes__REASON_CANNOT_TICKET", 103);
define("GatewayCodes__REASON_DECLINED", 104);
define("GatewayCodes__REASON_DECLINED_OVERLIMIT", 105);
define("GatewayCodes__REASON_DECLINED_CVV2", 106);
define("GatewayCodes__REASON_DECLINED_EXPIRED", 107);
define("GatewayCodes__REASON_DECLINED_CALL", 108);
define("GatewayCodes__REASON_DECLINED_PICKUP", 109);
define("GatewayCodes__REASON_DECLINED_EXCESSIVEUSE", 110);
define("GatewayCodes__REASON_DECLINED_INVALID_CARDNO", 111);
define("GatewayCodes__REASON_DECLINED_INVALID_EXPIRATION", 112);
define("GatewayCodes__REASON_BANK_UNAVAILABLE", 113);
define("GatewayCodes__REASON_EMPTY_BATCH", 114);
define("GatewayCodes__REASON_BATCH_REJECTED", 115);
define("GatewayCodes__REASON_DUPLICATE_BATCH", 116);
define("GatewayCodes__REASON_DECLINED_AVS", 117);

define("GatewayCodes__REASON_RISK_FAIL", 200);

define("GatewayCodes__REASON_DNS_FAILURE", 300);
define("GatewayCodes__REASON_UNABLE_TO_CONNECT", 301);
define("GatewayCodes__REASON_REQUEST_XMIT_ERROR", 302);
define("GatewayCodes__REASON_RESPONSE_READ_TIMEOUT", 303);
define("GatewayCodes__REASON_RESPONSE_READ_ERROR", 304);
define("GatewayCodes__REASON_SERVICE_UNAVAILABLE", 305);
define("GatewayCodes__REASON_CONNECTION_UNAVAILABLE", 306);
define("GatewayCodes__REASON_BUGCHECK", 307);
define("GatewayCodes__REASON_UNHANDLED_EXCEPTION", 308);
define("GatewayCodes__REASON_SQL_EXCEPTION", 309);
define("GatewayCodes__REASON_SQL_INSERT_ERROR", 310);
define("GatewayCodes__REASON_BANK_CONNECT_ERROR", 311);
define("GatewayCodes__REASON_BANK_XMIT_ERROR", 312);
define("GatewayCodes__REASON_BANK_READ_ERROR", 313);
define("GatewayCodes__REASON_BANK_DISCONNECT_ERROR", 314);
define("GatewayCodes__REASON_BANK_TIMEOUT_ERROR", 315);
define("GatewayCodes__REASON_BANK_PROTOCOL_ERROR", 316);
define("GatewayCodes__REASON_ENCRYPTION_ERROR", 317);
define("GatewayCodes__REASON_BANK_XMIT_RETRIES", 318);
define("GatewayCodes__REASON_BANK_RESPONSE_RETRIES", 319);
define("GatewayCodes__REASON_BANK_REDUNDANT_RESPONSES", 320);

define("GatewayCodes__REASON_XML_ERROR", 400);
define("GatewayCodes__REASON_INVALID_URL", 401);
define("GatewayCodes__REASON_INVALID_TRANSACTION", 402);
define("GatewayCodes__REASON_INVALID_CARDNO", 403);
define("GatewayCodes__REASON_INVALID_EXPIRATION", 404);
define("GatewayCodes__REASON_INVALID_AMOUNT", 405);
define("GatewayCodes__REASON_INVALID_MERCHANT_ID", 406);
define("GatewayCodes__REASON_INVALID_MERCHANT_ACCOUNT", 407);
define("GatewayCodes__REASON_INCOMPATABLE_CARDTYPE", 408);
define("GatewayCodes__REASON_NO_SUITABLE_ACCOUNT", 409);
define("GatewayCodes__REASON_INVALID_REFGUID", 410);
define("GatewayCodes__REASON_INVALID_ACCESS_CODE", 411);
define("GatewayCodes__REASON_INVALID_CUSTDATA_LENGTH", 412);
define("GatewayCodes__REASON_INVALID_EXTDATA_LENGTH", 413);
define("GatewayCodes__REASON_INVALID_CUSTOMER_ID", 414);
