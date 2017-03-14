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

use Tygh\Registry;

function fn_exim_set_em_subscriber_company($subscriber_id, $company_name)
{
    $company_id = fn_exim_set_company('em_subscribers', 'subscriber_id', $subscriber_id, $company_name);
}

function fn_import_check_em_subscriber_company_id(&$primary_object_id, &$object, &$pattern, &$options, &$processed_data, &$processing_groups, &$skip_record)
{
    if (!empty($primary_object_id) && Registry::get('runtime.company_id')) {
        $company_id = db_get_field('SELECT company_id FROM ?:em_subscribers WHERE company_id = ?s', $primary_object_id);

        if ($company_id != Registry::get('runtime.company_id')) {
            $processed_data['S']++;
            $skip_record = true;
        }
    }
}
