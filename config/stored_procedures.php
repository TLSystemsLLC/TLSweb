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
        'spFactoringCo_Search' => [
            'params' => [
                'Search'  => 'string',
                'MaxRows' => 'int',
            ],
        ],
        'spFactoringCo_Get' => [
            'params' => ['ID' => 'int'],
        ],
        'spFactoringCo_Delete' => [
            'params' => ['ID' => 'int'],
        ],
        'spFactoringCo_Save' => [
            'params' => [
                'ID'      => 'int',
                'Name'    => 'string',
                'Address' => 'string',
                'City'    => 'string',
                'State'   => 'string',
                'Zip'     => 'string',
                'ABA'     => 'string',
                'Account' => 'string',
                'Email'   => 'string',
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
            'params' => ['ShowInactive' => 'int'],
        ],

        'UpdateMenuItem' => [
            'params' => [
                'MenuKey' => 'string',
                'Active'  => 'int',
            ],
        ],

        // 'spValidationItem_Get' => ['params' => ['Item' => 'string']],
    ],
];
