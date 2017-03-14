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

use \Tygh\BlockManager\Block;

/**
 * Updates the owner menu identifier for cloned menu items.
 *
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 */
function fn_ult_clone_post_handler_static_data($from_company_id, $to_company_id, $cloning_results)
{
    $type_category = 'C';

    $menu_items = db_get_array(
        "SELECT param_id, param_3 FROM ?:static_data WHERE section = ?s AND company_id = ?i",
        'A', //Type menu
        $to_company_id
    );

    foreach ($menu_items as $item) {
        if (strpos($item['param_3'], $type_category) === 0) {
            $part = explode(':', $item['param_3']);
            $category_id = $part[1];

            $part[1] = isset($cloning_results['categories'][$category_id]) ? (int) $cloning_results['categories'][$category_id] : 0;
            $param = implode(':', $part);

            db_query(
                "UPDATE ?:static_data SET param_3 = ?s WHERE param_id = ?i",
                $param,
                $item['param_id']
            );
        }
    }
}

/**
 * Updates the content settings for cloned blocks.
 *
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 */
function fn_ult_clone_post_handler_layouts($from_company_id, $to_company_id, $cloning_results)
{
    /** @var \Tygh\BlockManager\Block $block_manage */
    $block_manage = Block::instance($to_company_id);
    $schema = fn_get_schema('clone', 'blocks');
    $types = array_keys($schema);

    if (empty($types)) {
        return;
    }

    foreach ($block_manage->getBlocksContentsByTypes($types) as $block) {
        if (!isset($schema[$block['type']]['function'])) {
            continue;
        }

        $config = isset($schema[$block['type']]['config']) ? $schema[$block['type']]['config'] : array();

        $block = call_user_func($schema[$block['type']]['function'], $block, $from_company_id, $to_company_id, $cloning_results, $config);

        if ($block !== false) {
            $block_manage->update(array(
                'type' => $block['type'],
                'block_id' => $block['block_id'],
                'properties' => $block['properties'],
                'content_data' => array(
                    'content' => $block['content'],
                    'lang_code' => $block['lang_code'],
                )
            ));
        }
    }

    $snapping_block_statuses = $block_manage->getSnappingBlockStatuses();

    foreach ($snapping_block_statuses as $item) {
        if (!empty($item['object_ids'])) {
            $object_ids = explode(',', $item['object_ids']);

            foreach ($object_ids as $key => $object_id) {
                if (isset($cloning_results[$item['object_type']][$object_id])) {
                    $object_ids[$key] = $cloning_results[$item['object_type']][$object_id];
                } elseif (!fn_ult_is_shared_object($item['object_type'], $object_id, $to_company_id)) {
                    unset($object_ids[$key]);
                }
            }

            $str_object_ids = implode(',', $object_ids);

            if ($item['object_ids'] != $str_object_ids) {
                $item['object_ids'] = $str_object_ids;

                $block_manage->updateStatuses($item);
            }
        }
    }
}