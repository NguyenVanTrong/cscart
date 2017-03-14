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

use Tygh\Tools\SecurityHelper;

$schema['store_location'] = array(
    SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
        'name' => SecurityHelper::ACTION_REMOVE_HTML,
        'description' => SecurityHelper::ACTION_SANITIZE_HTML,
        'city' => SecurityHelper::ACTION_REMOVE_HTML,
    )
);

return $schema;
