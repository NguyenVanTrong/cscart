<?php

namespace ExTwigmo\Core\Functions;

use Tygh\Languages\Values as LanguageValues;

class Lang
{
    public static function getCustomerLangVars($lang_code = CART_LANGUAGE)
    {
        $lang_vars = array_diff(self::getAllLangVars($lang_code), self::getAdminLangVars($lang_code));
        // We have to remove the twg_ prefix for these langvars
        $remove_prefix_for = array('is_logged_in', 'review_and_place_order');
        $prefix = 'twg_';
        foreach ($remove_prefix_for as $lang_var) {
            $with_prefix = $prefix . $lang_var;
            if (!isset($lang_vars[$with_prefix])) {
                continue;
            }
            $lang_vars[$lang_var] = $lang_vars[$with_prefix];
            unset($lang_vars[$with_prefix]);
        }
        return $lang_vars;
    }

    /**
    * Returns only active languages list (as lang_code => array(name, lang_code, status)
    *
    * @param bool $include_hidden if true get hiddenlanguages too
    * @return array Languages list
    */
    public static function getLanguages($include_hidden = false)
    {
        $language_condition =
            $include_hidden ?
                "WHERE status <> 'D'" :
                "WHERE status = 'A'";

        return db_get_hash_array(
            "SELECT lang_code, name FROM ?:languages ?p",
            'lang_code',
            $language_condition
        );
    }

    public static function getAllLangVars($lang_code = CART_LANGUAGE)
    {
        return self::getLangvarsByPrefix('twg', $lang_code);
    }

    private static function getAdminLangVars($lang_code = CART_LANGUAGE)
    {
        return self::getLangvarsByPrefix('twgadmin', $lang_code);
    }

    public static function getLangvarsByPrefix($prefix, $lang_code = CART_LANGUAGE)
    {
        if (class_exists('Tygh\Languages\Values')) {
            return LanguageValues::getLangVarsByPrefix($prefix, $lang_code);
        } else {
            return fn_get_lang_vars_by_prefix($prefix, $lang_code);
        }
    }
}
