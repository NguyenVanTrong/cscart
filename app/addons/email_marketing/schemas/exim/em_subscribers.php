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

include_once(Registry::get('config.dir.addons') . 'email_marketing/schemas/exim/em_subscribers.functions.php');

$schema = array(
    'section' => 'subscribers',
    'pattern_id' => 'em_subscribers',
    'name' => __('subscribers'),
    'key' => array('subscriber_id'),
    'table' => 'em_subscribers',
    'permissions' => array(
        'import' => 'manage_email_marketing',
        'export' => 'view_email_marketing',
    ),
    'range_options' => array (
        'selector_url' => 'em_subscribers.manage',
        'object_name' => __('subscribers'),
    ),
    'references' => array(
        'companies' => array(
            'reference_fields' => array('company_id' => '&company_id'),
            'join_type' => 'LEFT',
            'import_skip_db_processing' => true
        )
    ),
    'post_processing' => array(
        'sync' => array(
            'function' => 'fn_em_exim_sync',
            'args' => array('$primary_object_ids', '$import_data', '$auth'),
            'import_only' => true,
        ),
    ),
    'export_fields' => array (
        'E-mail' => array (
            'db_field' => 'email',
            'required' => true,
            'alt_key' => true,
        ),
        'Name' => array (
            'db_field' => 'name',
            'required' => true,
        ),
        'Unsubscribe key' => array (
            'db_field' => 'unsubscribe_key',
        ),
        'Status' => array (
            'db_field' => 'status'
        ),
        'Language' => array (
            'db_field' => 'lang_code',
        ),
        'IP address' => array (
            'db_field' => 'ip_address',
            'process_get' => array('fn_ip_from_db', '#this'),
            'convert_put' => array('fn_ip_to_db', '#this')
        ),
        'Date' => array (
            'db_field' => 'timestamp',
            'process_get' => array('fn_timestamp_to_date', '#this'),
            'convert_put' => array('fn_date_to_timestamp', '#this'),
        ),
    ),
);

if (fn_allowed_for('ULTIMATE')) {
    $schema['export_fields']['Store'] = array(
        'table' => 'companies',
        'db_field' => 'company',
        'process_put' => array('fn_exim_set_em_subscriber_company', '#key', '#this'),
        'required' => true,
    );

    $schema['import_process_data']['check_em_subscriber_company_id'] = array(
        'function' => 'fn_import_check_em_subscriber_company_id',
        'args' => array('$primary_object_id', '$object', '$pattern', '$options', '$processed_data', '$processing_groups', '$skip_record'),
        'import_only' => true,
    );
}

return $schema;
