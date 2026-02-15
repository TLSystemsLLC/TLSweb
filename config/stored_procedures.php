<?php

return [
    // tenant DB stored procedures allowed to be called from the web app
    'tenant' => [
        'spCompany_Get' => [
            'params' => ['CompanyID' => 'int'],
        ],
        'spUser_Login' => [
            'params' => [
                'UserID'   => 'string',
                'Password' => 'string',
            ],
        ],
        'spUser_GetByID' => [
            'params' => ['UserID' => 'string'],
        ],
        'spUser_Menus' => [
            'params' => ['UserID' => 'string'],
        ],
        'spUser_Menu' => [
            'params' => [
                'UserID' => 'string',
                'MenuID' => 'int',
            ],
        ],
        'spUser_Menu_Save' => [
            'params' => [
                'UserID'   => 'string',
                'MenuName' => 'string',
                'Allowed'  => 'int',
            ],
        ],
        // add more as you expose them
    ],

    // global / shared procedures (master DB)
    'global' => [
        'getTenants' => [
            'params' => [],
        ],

        'GetMenuItems' => [
            'params' => [],
        ],

        // 'spValidationItem_Get' => ['params' => ['Item' => 'string']],
    ],
];
