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


namespace Ebay;
use Ebay\responses\Response;
use Ebay\requests\Request;

/**
 * Class TransactionLogger
 * @package Ebay
 */
class TransactionLogger
{
    /** Status request send */
    const STATUS_SEND = 1;

    /** Status request complete success */
    const STATUS_SUCCESS = 2;

    /** Status request complete fail */
    const STATUS_FAIL = 3;

    /**
     * Start request - add to log
     *
     * @param Request $request
     * @return int
     */
    public static function startRequest(Request $request)
    {
        $data = array(
            'request' => $request->getMethodName(),
            'status' => self::STATUS_SEND,
            'error_count' => 0,
            'warning_count' => 0,
            'success_rate' => 0,
            'start_datetime' => time(),
            'end_datetime' => null
        );

        return (int) db_query('INSERT INTO ?:ebay_transaction_log ?e', $data);
    }

    /**
     * End request - update log
     *
     * @param int $id
     * @param Response $response
     */
    public static function endRequest($id, Response $response)
    {
        $data = array(
            'status' => $response->isSuccess() ? self::STATUS_SUCCESS : self::STATUS_FAIL,
            'error_count' => count($response->getErrors()),
            'warning_count' => count($response->getWarnings()),
            'success_rate' => $response->getSuccessRate(),
            'end_datetime' => time()
        );

        db_query('UPDATE ?:ebay_transaction_log SET ?u WHERE id = ?i', $data, $id);
    }

    /**
     * Request failed
     *
     * @param int $id
     * @param int $error_count
     */
    public static function failRequest($id, $error_count = 0)
    {
        $data = array(
            'status' => self::STATUS_FAIL,
            'error_count' => $error_count,
            'warning_count' => 0,
            'success_rate' => 0,
            'end_datetime' => time()
        );

        db_query('UPDATE ?:ebay_transaction_log SET ?u WHERE id = ?i', $data, $id);
    }
}