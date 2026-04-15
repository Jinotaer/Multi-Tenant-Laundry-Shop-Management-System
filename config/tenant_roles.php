<?php

return [
    [
        'slug' => 'owner',
        'name' => 'Owner',
        'description' => 'Tenant owner with full access.',
        'permissions' => ['*'],
    ],
    [
        'slug' => 'staff',
        'name' => 'Staff',
        'description' => 'Tenant staff account.',
        'permissions' => [],
    ],
    [
        'slug' => 'customer',
        'name' => 'Customer',
        'description' => 'Customer portal account.',
        'permissions' => [],
    ],
];
