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


return array(
    'order' => array(
        'class' => '\Tygh\Template\Document\Order\Variables\OrderVariable',
        'arguments' => array('#context', '#config', '@formatter'),
        'alias' => 'o',
    ),
    'company' => array(
        'class' => '\Tygh\Template\Document\Order\Variables\CompanyVariable',
        'alias' => 'c',
        'email_separator' => '<br/>'
    ),
    'user' => array(
        'class' => '\Tygh\Template\Document\Variables\GenericVariable',
        'alias' => 'u',
        'data' => function (\Tygh\Template\Document\Order\Context $context) {
            return $context->getOrder()->getUser();
        },
        'attributes' => function () {
            $attributes = array('email');
            $group_fields = fn_get_profile_fields('I');
            $sections = array('C', 'B', 'S');

            foreach ($sections as $section) {
                if (isset($group_fields[$section])) {
                    foreach ($group_fields[$section] as $field) {
                        if (!empty($field['field_name'])) {
                            $attributes[] = $field['field_name'];

                            if (in_array($field['field_type'], array('A', 'O'))) {
                                $attributes[] = $field['field_name'] . '_descr';
                            }
                        }
                    }
                }

                $attributes[strtolower($section) . '_fields']['[0..N]'] = array(
                    'name', 'value'
                );
            }

            return $attributes;
        }
    ),
    'payment' => array(
        'class' => '\Tygh\Template\Document\Variables\GenericVariable',
        'alias' => 'p',
        'data' => function (\Tygh\Template\Document\Order\Context $context) {
            $payment = $context->getOrder()->getPayment();

            if (empty($payment['surcharge_title'])) {
                $payment['surcharge_title'] = __('payment_surcharge', array(), $context->getLangCode());
            }

            return $payment;
        },
        'attributes' => array(
            'payment_id', 'payment', 'description', 'payment_category', 'surcharge_title', 'instructions',
            'status', 'a_surcharge', 'p_surcharge', 'processor', 'processor_type', 'processor_status'
        )
    ),
    'settings' => array(
        'class' => '\Tygh\Template\Document\Variables\SettingsVariable',
    ),
    'runtime' => array(
        'class' => '\Tygh\Template\Document\Variables\RuntimeVariable'
    )
);