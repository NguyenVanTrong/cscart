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

$schema = array(
    'product' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'shortname' => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_description' => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_keywords' => SecurityHelper::ACTION_REMOVE_HTML,
            'search_words' => SecurityHelper::ACTION_REMOVE_HTML,
            'page_title' => SecurityHelper::ACTION_REMOVE_HTML,
            'age_warning_message' => SecurityHelper::ACTION_REMOVE_HTML,
            'product_code' => SecurityHelper::ACTION_REMOVE_HTML,
            'short_description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'full_description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'promo_text' => SecurityHelper::ACTION_SANITIZE_HTML,
            'product' => SecurityHelper::ACTION_SANITIZE_HTML,
        )
    ),
    'category' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'meta_keywords' => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_description' => SecurityHelper::ACTION_REMOVE_HTML,
            'page_title' => SecurityHelper::ACTION_REMOVE_HTML,
            'age_warning_message' => SecurityHelper::ACTION_REMOVE_HTML,
            'description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'category' => SecurityHelper::ACTION_SANITIZE_HTML,
        )
    ),
    'company' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'company' => SecurityHelper::ACTION_REMOVE_HTML,
            'company_description' => SecurityHelper::ACTION_SANITIZE_HTML,
        )
    ),
    'page' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'page' => SecurityHelper::ACTION_REMOVE_HTML,
            'description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'page_title' => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_description' => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_keywords' => SecurityHelper::ACTION_REMOVE_HTML,
        )
    ),
    'product_option' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'option_name' => SecurityHelper::ACTION_REMOVE_HTML,
            'description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'comment' => SecurityHelper::ACTION_REMOVE_HTML,
            'incorrect_message' => SecurityHelper::ACTION_REMOVE_HTML,
        )
    ),
    'promotion' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'name' => SecurityHelper::ACTION_REMOVE_HTML,
            'short_description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'detailed_description' => SecurityHelper::ACTION_SANITIZE_HTML,
        )
    ),
    'product_feature' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'description' => SecurityHelper::ACTION_REMOVE_HTML,
            'full_description' => SecurityHelper::ACTION_SANITIZE_HTML,
        )
    ),
    'block' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'name' => SecurityHelper::ACTION_SANITIZE_HTML,
        )
    ),
    'shipping' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'shipping' => SecurityHelper::ACTION_REMOVE_HTML,
            'description' => SecurityHelper::ACTION_SANITIZE_HTML,
        )
    ),
    'status' => array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
            'description' => SecurityHelper::ACTION_REMOVE_HTML,
            'email_subj' => SecurityHelper::ACTION_REMOVE_HTML,
            'email_header' => SecurityHelper::ACTION_SANITIZE_HTML,
        )
    ),
);

return $schema;
