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

$schema = array (
    'table' => 'discussion',
    'object_name' => 'review',
    'fields' => array (
        'name' => array (
            'db_field' => 'name'
        ),
        'rating' => array (
            'db_field' => 'rating_value'
        ),
        'message' => array (
            'db_field' => 'message'
        )
    )
);
return $schema;
