<?php

$schema['call_request'] = array(
    'content' => array(
        'phone_number' => array(
            'type' => 'function',
            'function' => array('fn_call_requests_get_splited_phone')
        )
    ),
    'templates' => 'addons/call_requests/blocks/call_request.tpl',
    'wrappers' => 'blocks/wrappers',
    'cache' => true,
);

return $schema;