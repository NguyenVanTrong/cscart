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
 * Updates properties settings of the block rss_feed with filled by blog.
 *
 * @param array $block              Block data
 * @param int   $from_company_id    Base company identifier
 * @param int   $to_company_id      Target company identifier
 * @param array $cloning_results    List of cloned identifiers by object type and base identifier of object (categories => [from_id => to_id], etc)
 *
 * @return array|false  Returns block data if need update, otherwise false
 */
function fn_ult_clone_layout_block_rss_feed_filling_by_blog($block, $from_company_id, $to_company_id, $cloning_results)
{
    if (!empty($block['properties']['filling']['blog']['parent_page_id'])) {
        $page_id = $block['properties']['filling']['blog']['parent_page_id'];

        if (!fn_ult_is_shared_object('pages', $page_id, $to_company_id)) {
            $block['properties']['filling']['blog']['parent_page_id'] = 0;
            return $block;
        }
    }

    return false;
}
