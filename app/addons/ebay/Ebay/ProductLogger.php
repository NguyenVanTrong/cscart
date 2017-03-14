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

/**
 * Class ProductLogger
 * @package Ebay
 */
class ProductLogger
{
    /** Type info */
    const TYPE_INFO = 1;
    /** Type warning */
    const TYPE_WARNING = 2;
    /** Type error */
    const TYPE_ERROR = 3;

    /** Action export product */
    const ACTION_EXPORT_PRODUCT = 1;
    /** Action update product */
    const ACTION_UPDATE_PRODUCT = 2;
    /** Action upload product image */
    const ACTION_UPLOAD_IMAGE = 3;
    /** Action get product status */
    const ACTION_GET_PRODUCT_STATUS = 4;
    /** Action ending sale product */
    const ACTION_END_PRODUCT = 5;

    /**
     * Save log message
     *
     * @param int $action
     * @param Product $product
     * @param int $type
     * @param string $message
     * @param null|string $code
     * @return int|false
     */
    protected static function log($action, Product $product, $type, $message, $code = null)
    {
        $message = trim($message);
        $template = $product->getTemplate();

        $data = array(
            'product_id' => $product->id,
            'template_id' => $product->template_id,
            'product_name' => $product->original_title,
            'template_name' => $template ? $template->name : '-',
            'message' => $message,
            'code' => $code,
            'action' => $action,
            'type' => $type,
            'datetime' => time()
        );

        return db_query('INSERT INTO ?:ebay_product_log ?e', $data);
    }

    /**
     * Save warning message
     *
     * @param int $action
     * @param Product $product
     * @param string $message
     * @param null|string $code
     * @return int|false
     */
    public static function warning($action, Product $product, $message, $code = null)
    {
        return static::log($action, $product, static::TYPE_WARNING, $message, $code);
    }

    /**
     * Save error message
     *
     * @param int $action
     * @param Product $product
     * @param string $message
     * @param null|string $code
     * @return int|false
     */
    public static function error($action, Product $product, $message, $code = null)
    {
        return static::log($action, $product, static::TYPE_ERROR, $message, $code);
    }

    /**
     * Save info message
     *
     * @param int $action
     * @param Product $product
     * @param string $message
     * @param null|string $code
     * @return int|false
     */
    public static function info($action, Product $product, $message, $code = null)
    {
        return static::log($action, $product, static::TYPE_INFO, $message, $code);
    }

    /**
     * Return list log items by filter
     *
     * @param array $params
     * @param int $items_per_page
     * @return array
     */
    public static function getList($params, $items_per_page = 0)
    {
        // Set default values to input params
        $default_params = array (
            'page' => 1,
            'items_per_page' => $items_per_page
        );

        $types = static::getTypes();
        $actions = static::getActions();
        $params = array_merge($default_params, $params);
        $condition = '';
        $limit = '';
        $sortings = array (
            'id' => 'id',
            'datetime' => 'datetime',
        );

        if (isset($params['template_id']) && fn_is_not_empty($params['template_id'])) {
            $condition .= db_quote(' AND template_id = ?i', $params['template_id']);
        }

        if (isset($params['product_id']) && fn_is_not_empty($params['product_id'])) {
            $condition .= db_quote(' AND product_id = ?i', $params['product_id']);
        }

        if (isset($params['product_ids']) && fn_is_not_empty($params['product_ids'])) {
            $condition .= db_quote(' AND product_id IN (?n)', $params['product_ids']);
        }

        if (isset($params['code']) && fn_is_not_empty($params['code'])) {
            $condition .= db_quote(' AND product_id = ?s', $params['code']);
        }

        if (isset($params['type']) && fn_is_not_empty($params['type'])) {
            $condition .= db_quote(' AND type = ?i', $params['type']);
        }

        if (isset($params['action']) && fn_is_not_empty($params['action'])) {
            $condition .= db_quote(' AND action = ?i', $params['action']);
        }

        if (!empty($params['period']) && $params['period'] != 'A') {
            list($time_from, $time_to) = fn_create_periods($params);

            $condition .= db_quote(' AND (datetime >= ?i AND datetime <= ?i)', $time_from, $time_to);
        }

        $sorting = db_sort($params, $sortings, 'id', 'desc');

        if (!empty($params['items_per_page'])) {
            $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:ebay_product_log WHERE 1 {$condition}");
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }

        $result = db_get_array("SELECT * FROM ?:ebay_product_log WHERE 1 {$condition} {$sorting} {$limit}");

        foreach ($result as &$item) {
            switch ($item['type']) {
                case static::TYPE_ERROR:
                    $item['type_code'] = 'error';
                    break;
                case static::TYPE_INFO:
                    $item['type_code'] = 'info';
                    break;
                case static::TYPE_WARNING:
                    $item['type_code'] = 'warning';
                    break;
                default:
                    $item['type_code'] = 'undefined';
                    break;
            }

            $item['type_name'] = isset($types[$item['type']]) ? $types[$item['type']] : 'undefined';
            $item['action_name'] = isset($actions[$item['action']]) ? $actions[$item['action']] : 'undefined';
        }

        unset($item);

        return array($result, $params);
    }

    /**
     * Return type names
     * @return array
     */
    public static function getTypes()
    {
        return array(
            static::TYPE_ERROR => __("error"),
            static::TYPE_INFO => __("notice"),
            static::TYPE_WARNING => __("warning"),
        );
    }

    /**
     * Return action names
     * @return array
     */
    public static function getActions()
    {
        return array(
            static::ACTION_EXPORT_PRODUCT => __("ebay_action_export_product"),
            static::ACTION_UPDATE_PRODUCT => __("ebay_action_update_product"),
            static::ACTION_UPLOAD_IMAGE => __("ebay_action_upload_image_product"),
            static::ACTION_GET_PRODUCT_STATUS => __("ebay_action_get_status_product"),
            static::ACTION_END_PRODUCT => __("ebay_action_end_product")
        );
    }

    /**
     * Clean logs
     */
    public static function clean()
    {
        db_query("TRUNCATE TABLE ?:ebay_product_log");
    }
}
