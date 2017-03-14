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

/**
 * Updates content settings of the block menu.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_menu($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['content']['menu'])) {
        $menu_id = $block['content']['menu'];
        $new_menu_id = isset($cloning_results['menus'][$menu_id]) ? (int) $cloning_results['menus'][$menu_id] : 0;
        $block['content']['menu'] = $new_menu_id;

        return $block;
    }

    return false;
}

/**
 * Updates content settings of the block configured by filling.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 * @param array $config             Handler config
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_configured_by_filling($block, $from_company_id, $to_company_id, $cloning_results, $config)
{
    $filling = !empty($block['content']['items']['filling']) ? $block['content']['items']['filling'] : null;

    if ($filling && isset($config['fillings_handlers'][$filling])) {
        $updates_cnt = 0;

        foreach ($config['fillings_handlers'][$filling] as $handler) {
            $result = call_user_func($handler, $block, $from_company_id, $to_company_id, $cloning_results);

            if ($result) {
                $block = $result;
                $updates_cnt++;
            }
        }

        if ($updates_cnt) {
            return $block;
        }
    }

    return false;
}

/**
 * Updates property settings of the block configured by properties.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 * @param array $config             Handler config
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_configured_by_properties($block, $from_company_id, $to_company_id, $cloning_results, $config)
{
    if (!empty($block['properties'])) {
        foreach ($block['properties'] as $key => $item) {
            if (isset($config['properties_handlers'][$key])) {
                $updates_cnt = 0;

                foreach ($config['properties_handlers'][$key] as $handler) {
                    $result = call_user_func($handler, $block, $from_company_id, $to_company_id, $cloning_results);

                    if ($result) {
                        $block = $result;
                        $updates_cnt++;
                    }
                }

                if ($updates_cnt) {
                    return $block;
                }
            }
        }
    }

    return false;
}

/**
 * Updates content settings of the block products with filled by product identifiers.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_products_filling_by_manually($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['content']['items']['item_ids'])) {
        $product_ids = explode(',', $block['content']['items']['item_ids']);

        foreach ($product_ids as $key => $product_id) {
            if (!fn_ult_is_shared_object('products', $product_id, $to_company_id)) {
                unset($product_ids[$key]);
            }
        }

        $block['content']['items']['item_ids'] = implode(',', $product_ids);

        return $block;
    }

    return false;
}

/**
 * Updates content settings of the block products with filled by category identifiers.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_products_filling_by_category($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['content']['items']['cid'])) {
        $category_ids = explode(',', $block['content']['items']['cid']);

        foreach ($category_ids as $key => $category_id) {
            if (isset($cloning_results['categories'][$category_id])) {
                $category_ids[$key] = $cloning_results['categories'][$category_id];
            } else {
                unset($category_ids[$key]);
            }
        }

        $block['content']['items']['cid'] = implode(',', $category_ids);

        return $block;
    }

    return false;
}

/**
 * Updates content settings of the block categories with filled by category identifiers.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_categories_filling_by_manually($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['content']['items']['item_ids'])) {
        $category_ids = explode(',', $block['content']['items']['item_ids']);

        foreach ($category_ids as $key => $category_id) {
            if (!empty($cloning_results['categories'][$category_id])) {
                $category_ids[$key] = $cloning_results['categories'][$category_id];
            } else {
                unset($category_ids[$key]);
            }
        }

        $block['content']['items']['item_ids'] = implode(',', $category_ids);

        return $block;
    }

    return false;
}

/**
 * Updates content settings of the block categories with filled by full_tree_cat.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_categories_filling_by_full_tree_cat($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['content']['items']['parent_category_id'])) {
        $category_id = $block['content']['items']['parent_category_id'];

        if (!empty($cloning_results['categories'][$category_id])) {
            $category_id = $cloning_results['categories'][$category_id];
        } else {
            $category_id = 0;
        }

        $block['content']['items']['parent_category_id'] = $category_id;

        return $block;
    }

    return false;
}

/**
 * Updates content settings of the block pages with filled by dynamic_tree_pages or full_tree_pages.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_pages_filling_by_tree($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['content']['items']['parent_page_id'])) {
        $page_id = $block['content']['items']['parent_page_id'];

        if (!fn_ult_is_shared_object('pages', $page_id, $to_company_id)) {
            $page_id = 0;
        }

        $block['content']['items']['parent_page_id'] = $page_id;

        return $block;
    }

    return false;
}

/**
 * Updates content settings of the block pages with filled by manually.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_pages_filling_by_manually($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['content']['items']['item_ids'])) {
        $page_ids = explode(',', $block['content']['items']['item_ids']);

        foreach ($page_ids as $key => $page_id) {
            if (!fn_ult_is_shared_object('pages', $page_id, $to_company_id)) {
                unset($page_ids[$key]);
            }
        }

        $block['content']['items']['item_ids'] = implode(',', $page_ids);

        return $block;
    }

    return false;
}

/**
 * Updates content settings of the block product_filters with filled by manually.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_product_filters_filling_by_manually($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['content']['items']['item_ids'])) {
        $filter_ids = explode(',', $block['content']['items']['item_ids']);

        foreach ($filter_ids as $key => $filter_id) {
            if (!fn_ult_is_shared_object('product_filters', $filter_id, $to_company_id)) {
                unset($filter_ids[$key]);
            }
        }

        $block['content']['items']['item_ids'] = implode(',', $filter_ids);

        return $block;
    }

    return false;
}
