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

$schema['mailing_lists'] = array(
    'controller' => 'mailing_lists',
    'mode' => 'update',
    'type' => 'tpl_tabs',
    'params' => array(
        'object_id' => '@list_id',
        'object' => 'mailing_lists'
    ),
    'table' => array(
        'name' => 'mailing_lists',
        'key_field' => 'list_id',
    ),
    'have_owner' => false,
);

return $schema;
