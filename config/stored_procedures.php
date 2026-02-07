<?php

return [
    // tenant DB stored procedures allowed to be called from the web app
    'tenant' => [
        'spCompany_Get' => [
            'params' => ['CompanyID' => 'int'],
        ],
        // add more as you expose them
    ],

    // global / shared procedures (master DB)
    'global' => [
        'getTenants' => [
            'params' => [],
        ],

        // 'spValidationItem_Get' => ['params' => ['Item' => 'string']],
    ],
];
