<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'alioss',
        ],
        'upload_config_init' => [
            'alioss',
        ],
        'upload_delete' => [
            'alioss',
        ],
        'sms_send' => [
            'alisms',
        ],
        'sms_notice' => [
            'alisms',
        ],
        'sms_check' => [
            'alisms',
        ],
    ],
    'route' => [],
    'priority' => [],
];
