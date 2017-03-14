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

//
// Defined variables
//

define('MAX_PAYPAL_PRODUCTS', 100);
define('MAX_PAYPAL_DESCR_LENGTH', 126);

define('PAYPAL_ORDER_STATUS_REFUNDED', 'Refunded');
define('PAYPAL_ORDER_STATUS_COMPLETED', 'Completed');

define('PAYPAL_PARTIAL_REFUND_IGNORE', 'ignore');
