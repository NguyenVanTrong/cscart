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

$schema['testimonials'] = array(
    'templates' => array(
        'addons/discussion/blocks/testimonials.tpl' => array(),
    ),
    'wrappers' => 'blocks/wrappers',
    'settings' => array(
        'limit' => array (
            'type' => 'input',
            'default_value' => '10'
        ),
        'random' => array (
            'type' => 'checkbox',
            'default_value' => 'N'
        ),
        'not_scroll_automatically' => array (
            'type' => 'checkbox',
            'default_value' => 'N'
        ),
        'speed' =>  array (
            'type' => 'input',
            'default_value' => 400
        ),
        'pause_delay' =>  array (
            'type' => 'input',
            'default_value' => 3
        ),
        'item_quantity' =>  array (
            'type' => 'input',
            'default_value' => 3
        ),
        'outside_navigation' => array (
            'type' => 'checkbox',
            'default_value' => 'Y'
        )
    ),
    'cache' => array(
        'update_handlers' => array ('discussion', 'discussion_messages', 'discussion_posts', 'discussion_rating'),
    )
);

$schema['products']['content']['items']['fillings']['rating'] = array (
    'params' => array (
        'rating' => true,
        'sort_by' => 'rating'
    ),
);
$schema['products']['cache']['update_handlers'][] = 'discussion_rating';

$schema['categories']['content']['items']['fillings']['rating'] = array (
    'params' => array (
        'rating' => true,
        'sort_by' => 'rating'
    ),
);

if (!empty($schema['categories']['cache']['update_handlers'])) {
    $schema['categories']['cache']['update_handlers'][] = 'discussion_rating';
}

$schema['pages']['content']['items']['fillings']['rating'] = array (
    'params' => array (
        'rating' => true,
        'sort_by' => 'rating'
    ),
);

if (!empty($schema['pages']['cache']['update_handlers'])) {
    $schema['pages']['cache']['update_handlers'][] = 'discussion_rating';
}

$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'discussion';
$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'discussion_messages';
$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'discussion_posts';
$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'discussion_rating';

$schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'discussion';
$schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'discussion_messages';
$schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'discussion_posts';
$schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'discussion_rating';

return $schema;
