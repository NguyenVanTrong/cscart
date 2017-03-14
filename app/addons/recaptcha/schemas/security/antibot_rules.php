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

$schema = array(
    'profiles' => array(
        'update' => array(
            'request_method' => 'POST',
            'verification_scenario' => 'register',
            'save_post_data' => array(
                'user_data',
            ),
            'rewrite_controller_status' => array(
                CONTROLLER_STATUS_REDIRECT,
                'profiles.add',
            ),
        ),
    ),

    'orders' => array(
        'track_request' => array(
            'request_method' => 'POST',
            'verification_scenario' => 'track_orders',
            'terminate_process' => true,
        ),
    ),

    'auth' => array(
        'login' => array(
            'request_method' => 'POST',
            'verification_scenario' => 'login',
            'save_post_data' => array(
                'user_login',
            ),
            'rewrite_controller_status' => array(
                CONTROLLER_STATUS_REDIRECT,
            ),
        ),
    ),


    'checkout' => array(
        'add_profile' => array(
            'request_method' => 'POST',
            'verification_scenario' => 'register',
            'save_post_data' => array(
                'user_data',
            ),
            'rewrite_controller_status' => array(
                CONTROLLER_STATUS_REDIRECT,
                'checkout.checkout?login_type=register',
            ),
        ),
    ),
);

if (fn_allowed_for('MULTIVENDOR')) {
    $schema['companies']['apply_for_vendor'] = array(
        'request_method' => 'POST',
        'verification_scenario' => 'apply_for_vendor_account',
        'save_post_data' => array(
            'user_data',
            'company_data',
        ),
        'rewrite_controller_status' => array(
            CONTROLLER_STATUS_REDIRECT,
            'companies.apply_for_vendor',
        ),
    );
}


return $schema;