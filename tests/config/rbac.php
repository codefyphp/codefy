<?php

return [

    'permissions' => [
        'admin' => [
            'description' => 'Super administrator',
            'permissions' => [
                'admin:dashboard' => ['description' => 'Access to the dashboard.'],
                'admin:profile' => ['description' => 'Access to profile edit.'],
            ],
        ],
        'user' => [
            'description' => 'desc',
            'permissions' => [
                'user:perm1' => ['description' => 'desc1'],
                'user:perm2' => ['description' => 'desc2'],
                'user:perm3' => ['description' => 'desc3'],
            ],
        ],
    ],

    'roles' => [
        'admin' => [
            'description' => 'Administrator',
            'permissions' => ['admin'],
        ],
        'user' => [
            'description' => 'Regular user',
            'permissions' => ['user'],
        ]
    ],
];
