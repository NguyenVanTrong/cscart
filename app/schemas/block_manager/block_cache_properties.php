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

// Global block cache parameters,
// which are applied to every block type that should be cached
$schema = array(

    // Cache of an every block should be deleted in case of:
    'update_handlers' => array(

        // Any add-on was installed/removed/enabled/disabled
        'addons',

        // Store settings were changed
        'settings_objects',

        // Blocks were modified
        'bm_blocks',
        'bm_blocks_descriptions',
        'bm_blocks_content',
        'bm_block_statuses',
        'bm_snapping',

        // The anguages were installed or removed
        'languages',

        // Language values were modified
        'language_values',

        // Promotions were modified
        'promotions',
    ),

    'request_handlers' => array(),
    'session_handlers' => array(),
    'cookie_handlers' => array(),
    'auth_handlers' => array(),
    'callable_handlers' => array(),
);

if (fn_allowed_for('ULTIMATE')) {
    // Very common block cache dependency
    $schema['update_handlers'][] = 'ult_objects_sharing';
}

return $schema;