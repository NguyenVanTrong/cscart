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

namespace Tygh\Template\Document\Variables;


use Tygh\Registry;
use Tygh\Template\IVariable;

/**
 * The class of the `runtime` variable; it allows access to environment data.
 *
 * @package Tygh\Template\Document\Variables
 */
class RuntimeVariable implements IVariable
{
    public $lang_code;
    public $company_id;
    public $primary_currency_code;
    public $secondary_currency_code;
    public $images_dir;

    public function __construct()
    {
        $this->lang_code = CART_LANGUAGE;
        $this->company_id = fn_get_runtime_company_id();
        $this->primary_currency_code = CART_PRIMARY_CURRENCY;
        $this->secondary_currency_code = CART_SECONDARY_CURRENCY;

        $path_rel = fn_get_theme_path('[relative]/[theme]', 'A', $this->company_id);
        $this->images_dir = Registry::get('config.current_location') . '/' . $path_rel . '/media/images';
    }
}