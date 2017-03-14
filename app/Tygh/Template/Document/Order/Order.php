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

namespace Tygh\Template\Document\Order;

/**
 * The proxy class that serves to retrieve data about the order.
 *
 * @package Tygh\Template\Document\Order
 */
class Order
{
    /** @var array  */
    public $data = array();

    /** @var string */
    public $lang_code;

    /** @var array */
    protected $user = array();

    /** @var array */
    protected $status_data = array();

    /**
     * Order constructor.
     *
     * @param int       $order_id   Order identifier.
     * @param string    $lang_code  Language code.
     */
    public function __construct($order_id, $lang_code = DESCR_SL)
    {
        $this->data = fn_get_order_info($order_id, false, true, false, false, $lang_code);
        $this->lang_code = $lang_code;
    }

    /**
     * Gets order identifier.
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->data['order_id'];
    }

    /**
     * Gets order company identifier.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return (int) $this->data['company_id'];
    }

    /**
     * Gets list of products.
     *
     * @return array
     */
    public function getProducts()
    {
        return $this->data['products'];
    }

    /**
     * Gets list of group products.
     *
     * @return array
     */
    public function getGroupProducts()
    {
        return $this->data['group_products'];
    }

    /**
     * Gets list of taxes.
     *
     * @return array
     */
    public function getTaxes()
    {
        return $this->data['taxes'];
    }

    /**
     * Gets order shippings.
     *
     * @return array
     */
    public function getShippings()
    {
        return isset($this->data['shipping']) ? $this->data['shipping'] : array();
    }

    /**
     * Gets order shipments.
     *
     * @return array
     */
    public function getShipments()
    {
        list($result) = fn_get_shipments_info(array('order_id' => $this->getId(), 'advanced_info' => true));
        
        return $result;
    }

    /**
     * Gets order payment data.
     *
     * @return array
     */
    public function getPayment()
    {
        return $this->data['payment_method'];
    }

    /**
     * Gets order status data.
     *
     * @param string|null $lang_code Language code.
     *
     * @return array
     */
    public function getStatusData($lang_code = null)
    {
        $lang_code = $lang_code ? $lang_code : $this->data['lang_code'];

        if (!isset($this->status_data[$lang_code])) {
            $this->status_data[$lang_code] = fn_get_status_data(
                $this->data['status'],
                STATUSES_ORDER,
                $this->getId(),
                $lang_code,
                $this->getCompanyId()
            );
        }
        return $this->status_data[$lang_code];
    }

    /**
     * Gets order user data.
     *
     * @param string|null $lang_code Language code.
     *
     * @return array
     */
    public function getUser($lang_code = null)
    {
        $lang_code = $lang_code ? $lang_code : $this->data['lang_code'];

        if (!isset($this->user[$lang_code])) {
            $user = array_intersect_key($this->data, array_flip(array('email')));
            $group_fields = fn_get_profile_fields('I', array(), $lang_code);
            $sections = array('C', 'B', 'S');

            foreach ($sections as $section) {
                $user[strtolower($section) . '_fields'] = array();

                if (isset($group_fields[$section])) {
                    foreach ($group_fields[$section] as $field) {
                        $value = fn_get_profile_field_value($this->data, $field);

                        if (!empty($field['field_name'])) {
                            if (in_array($field['field_type'], array('A', 'O'))) {
                                $user[$field['field_name'] . '_descr'] = $value;
                                $user[$field['field_name']] = isset($this->data[$field['field_name']]) ? $this->data[$field['field_name']] : $value;
                            } else {
                                $user[$field['field_name']] = $value;
                            }
                        } else {
                            $user[strtolower($section) . '_fields'][$field['field_id']] = array(
                                'name' => $field['description'],
                                'value' => $value
                            );
                        }
                    }
                }
            }

            $this->user[$lang_code] = $user;
        }

        return $this->user[$lang_code];
    }
}