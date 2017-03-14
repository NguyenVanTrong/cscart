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

use Tygh\BlockManager\Block;

function fn_le_update_block($field, $value, $id, $lang_code) {
    $block = Block::instance()->getById($id);
    $data = array(
        'block_id' => $id,
        'type' => $block['type'],
    );
    $description = array();
    if ($field == 'content') {
        $data['content_data'] = array(
            'lang_code' => $lang_code,
            'content' => array(
                'content' => $value
            ),
        );
    } elseif ($field == 'name') {
        $description = array(
            'lang_code' => $lang_code,
            'name' => $value,
        );
        $data['description'] = $description;
    } else {
        return;
    }
    Block::instance()->update($data, $description);
}
