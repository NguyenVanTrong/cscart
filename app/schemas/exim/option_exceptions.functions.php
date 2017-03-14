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

function fn_exim_get_product_option_exception($product_id, $combination, $set_delimiter, $lang_code = CART_LANGUAGE)
{
    $result = array();
    if (empty($combination)) {
        return '';
    }

    $product_options = fn_get_product_options($product_id, $lang_code);

    if (empty($product_options)) {
        return '';
    }

    $combination = unserialize($combination);
    foreach ($combination as $option_id => $variant_id) {
        switch ($variant_id) {
            case OPTION_EXCEPTION_VARIANT_ANY:
                // Any variant can be selected
                $result[] = fn_exim_wrap_value($product_options[$option_id]['option_name'], "|", '=') . '=#Any';
                break;
            case OPTION_EXCEPTION_VARIANT_NOTHING:
                // No variants can be selected
                $result[] = fn_exim_wrap_value($product_options[$option_id]['option_name'], "|", '=') . '=#No';
                break;
            default:
                // Specified variant
                $result[] = fn_exim_wrap_value($product_options[$option_id]['option_name'], "|", '=') . '='
                    . fn_exim_wrap_value($product_options[$option_id]['variants'][$variant_id]['variant_name'], "|", '=');

                break;
        }
    }

    $result = implode($set_delimiter, fn_exim_wrap_value($result, "'", $set_delimiter));

    return $result;
}

function fn_exim_set_product_option_exception($product_id, $combinations, &$counter, $set_delimiter, $lang_code)
{
    static $_product_id = 0;
    static $_product_option = array();

    $result = array();

    if ($_product_id != $product_id) {
        $_options = fn_get_product_options($product_id, $lang_code);
        foreach ($_options as $option) {
            $_product_option[$option['option_name']] = $option;
            unset($_product_option[$option['option_name']]['variants']);
            foreach ($option['variants'] as $variant) {
                $_product_option[$option['option_name']]['variants'][$variant['variant_name']] = $variant;
            }
        }
    }

    foreach ($combinations as $lang_code => $combination) {
        $combination = str_getcsv($combination, $set_delimiter, "'");
        foreach ($combination as $option_variant) {
            list($option, $variant) = str_getcsv($option_variant, '=', "|");
            $option = trim($option);
            $variant = trim($variant);

            $option_id = isset($_product_option[$option]) ? $_product_option[$option]['option_id'] : 0;

            if (strpos($variant, '#') !== false) {
                $variant_id = strtolower($variant) == '#no' ? OPTION_EXCEPTION_VARIANT_NOTHING : OPTION_EXCEPTION_VARIANT_ANY;
            } else {
                $variant_id = isset($_product_option[$option]['variants'][$variant]) ? $_product_option[$option]['variants'][$variant]['variant_id'] : 0;
            }

            if (empty($option_id) || empty($variant_id)) {
                $counter['S']++;

                return false;
            }

            $result[$option_id] = $variant_id;
        }
    }

    $combination = array(
        'product_id' => $product_id,
        'combination' => serialize($result),
    );

    if (fn_check_combination($result, $product_id)) {
        $counter['S']++;
    } else {
        db_query('INSERT INTO ?:product_options_exceptions ?e', $combination);
        $counter['N']++;
    }

    return $combination;
}

function fn_import_check_exception_combination_company_id(&$primary_object_id, &$object, &$pattern, &$options, &$processed_data, &$processing_groups, &$skip_record)
{
    if (Registry::get('runtime.company_id')) {
        if (empty($primary_object_id) && empty($object['product_id'])) {
            $processed_data['S']++;
            $skip_record = true;

            return false;
        }

        if (!empty($primary_object_id)) {
            $value = reset($primary_object_id);
            $field = key($primary_object_id);
            
            $company_id = db_get_field('SELECT company_id FROM ?:products WHERE ' . $field . ' = ?s', $value);
        } else {
            $company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $object['product_id']);
        }

        if ($company_id != Registry::get('runtime.company_id')) {
            $processed_data['S']++;
            $skip_record = true;
        }
    }
}
