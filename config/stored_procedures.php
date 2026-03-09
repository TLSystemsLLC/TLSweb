<?php

return [
    // tenant DB stored procedures allowed to be called from the web app
    'tenant' => [
        'spCompany_Get' => [
            'params' => ['CompanyID' => 'int'],
        ],
        'webCompanySearch' => [
            'params' => [
                'Search'   => 'string',
                'Page'     => 'int',
                'PageSize' => 'int',
            ],
        ],
        'webCompanyGet' => [
            'params' => ['CompanyID' => 'int'],
        ],
        'webCompanySave' => [
            'params' => [
                'CompanyID'                 => 'int',
                'CompanyName'               => 'string',
                'ShortName'                 => 'string',
                'MailingAddress'            => 'string',
                'MailingCity'               => 'string',
                'MailingState'              => 'string',
                'MailingZip'                => 'string',
                'ShippingAddress'           => 'string',
                'ShippingCity'              => 'string',
                'ShippingState'             => 'string',
                'ShippingZip'               => 'string',
                'MainPhone'                 => 'string',
                'MainFax'                   => 'string',
                'SCAC'                      => 'string',
                'DUNS'                      => 'string',
                'MC'                        => 'string',
                'DOT'                       => 'string',
                'FID'                       => 'string',
                'Active'                    => 'int',
                'FreightDetailPost'         => 'int',
                'ComdataInterface'          => 'int',
                'TranfloMobileInterface'    => 'int',
                'SystemRemitVendor'         => 'int',
                'APAccount'                 => 'float',
                'ARAccount'                 => 'float',
                'BadDebtAccount'            => 'float',
                'MiscAccount'               => 'float',
                'FreightRevAccount'         => 'float',
                'BrokerRevAccount'          => 'float',
                'FreightPayableAccount'     => 'float',
                'GeneralBankAccount'        => 'float',
                'SettlementBankAccount'     => 'float',
                'SettlementClearingAccount' => 'float',
                'InterCompanyClearing'      => 'float',
                'InterCompanyAR'            => 'float',
                'InterCompanyAP'            => 'float',
                'FrieghtRevExp'             => 'float',
                'CompanyFreightRevenue'     => 'float',
                'CompanyFreightExpense'     => 'float',
                'CompanyTruckFuelExpense'   => 'float',
                'CompanyReeferFuelExpense'  => 'float',
                'DriverAR'                  => 'float',
                'RetainedEarningsAccount'   => 'float',
                'Logo'                      => 'string', // IMAGE type in SQL, handled as string/binary
            ],
        ],
        'spUser_Login' => [
            'params' => [
                'UserID'   => 'string',
                'Password' => 'string',
            ],
        ],
        'webUserSearch' => [
            'params' => [
                'Search'   => 'string',
                'Page'     => 'int',
                'PageSize' => 'int',
            ],
        ],
        'spUser_GetByID' => [
            'params' => ['UserID' => 'string'],
        ],
        'spUsers_GetAll' => [
            'params' => [],
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
        'spUser_Save2' => [
            'params' => [
                'Key'               => 'int',
                'UserID'            => 'string',
                'TeamKey'           => 'int',
                'UserName'          => 'string',
                'FirstName'         => 'string',
                'LastName'          => 'string',
                'PasswordChanged'   => 'string',
                'Extension'         => 'int',
                'SatelliteInstalls' => 'int',
                'Password'          => 'string',
                'Email'             => 'string',
                'LastLogin'         => 'string',
                'HireDate'          => 'string',
                'RapidLogUser'      => 'int',
                'Active'            => 'int',
                'UserType'          => 'int',
                'CompanyID'         => 'int',
                'DivisionID'        => 'int',
                'DepartmentID'      => 'int',
                'Phone'             => 'string',
                'Fax'               => 'string',
            ],
        ],
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

        'spContactRequest_Save' => [
            'params' => [
                'Name'    => 'string',
                'Email'   => 'string',
                'Phone'   => 'string',
                'Message' => 'string',
            ],
        ],

        // 'spValidationItem_Get' => ['params' => ['Item' => 'string']],
    ],
];
