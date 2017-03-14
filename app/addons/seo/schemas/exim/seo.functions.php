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

/**
 * Generates SEO name for imported product
 *
 * @param int $object_id Product identificator
 * @param int $object_type One-letter object type identificator
 * @param string $object_name SEO-name to import with
 * @param array $product_name Product name for specified language code
 * @param int $index
 * @param string $dispatch
 * @param string $company_id Company identifier
 * @param string $lang_code Two-letter language code
 * @param string $company_name Company name product imported for
 * @return array SEO name for specified language code
 */
function fn_create_import_seo_name($object_id, $object_type = 'p', $object_name, $product_name, $index = 0, $dispatch = '', $company_id = '', $lang_code = CART_LANGUAGE, $company_name = '')
{
    if (empty($company_id) && !empty($company_name) && !Registry::get('runtime.company_id')) {
        $company_id = fn_get_company_id_by_name($company_name);
    }

    if (!is_array($object_name)) {
        $object_name = array($lang_code => $object_name);
    }

    $result = array();
    foreach ($object_name as $name_lang_code => $seo_name) {
        if (empty($seo_name)) {
            $seo_name = reset($product_name);
        }

        $result[$name_lang_code] = fn_create_seo_name($object_id, $object_type, $seo_name, $index, $dispatch, $company_id, $name_lang_code);
    }

    return $result;
}