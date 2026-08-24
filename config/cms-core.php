<?php

return [
    'user_model' => env('CMS_CORE_USER_MODEL'),

    'auth' => [
        'logout_redirect' => env('CMS_CORE_LOGOUT_REDIRECT', '/'),
    ],

    'admin_user' => [
        'name' => env('ADMIN_USER_NAME', 'Administrador'),
        'email' => env('ADMIN_USER_EMAIL'),
        'password' => env('ADMIN_USER_PASSWORD'),
    ],

    'updates' => [
        'enabled' => env('CMS_UPDATER_ENABLED', true),
        'channel' => env('CMS_UPDATE_CHANNEL', 'stable'),
        'packages' => [
            'pcteckserv/cms-core',
        ],
        'repositories' => [
            'pcteckserv/cms-core' => env('CMS_CORE_REPOSITORY_URL', 'https://github.com/pcteckserv/cmspcteckserv-core.git'),
        ],
        'github_token' => env('CMS_GITHUB_TOKEN'),
    ],
];
