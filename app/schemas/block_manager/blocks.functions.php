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

function fn_blocks_get_vendor_info()
{
    $company_id = isset($_REQUEST['company_id']) ? $_REQUEST['company_id'] : null;

    $company_data = fn_get_company_data($company_id);
    $company_data['logos'] = fn_get_logos($company_id);

    return $company_data;
}

/**
 * Decides whether to disable cache for "products" block.
 *
 * @param $block_data
 *
 * @return bool Whether to disable cache
 */
function fn_block_products_disable_cache($block_data)
{
    // Disable cache for "Recently viewed" filling
    if (isset($block_data['content']['items']['filling'])
        && $block_data['content']['items']['filling'] == 'recent_products'
    ) {
        return true;
    }

    return false;
}