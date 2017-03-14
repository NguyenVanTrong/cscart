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

$schema['pages']['send_form'] = array(
    'request_method' => 'POST',
    'verification_scenario' => 'form_builder',
    'save_post_data' => array(
        'form_values',
    ),
    'rewrite_controller_status' => array(
        CONTROLLER_STATUS_REDIRECT,
        function () {
            return 'pages.view?page_id=' . $_REQUEST['page_id'];
        }
    ),
);

return $schema;