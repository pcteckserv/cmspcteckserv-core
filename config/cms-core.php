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

    'site_options' => [
        'site_title' => env('CMS_SITE_TITLE', 'CMS PCTECK'),
        'site_description' => env('CMS_SITE_DESCRIPTION', 'Site institucional gerido pelo CMS PCTECK.'),
        'site_icon_url' => env('CMS_SITE_ICON_URL', '/favicon.ico'),
        'wordpress_url' => env('CMS_WORDPRESS_URL', 'https://cliente.exemplo.pt/admin'),
        'site_url' => env('CMS_SITE_URL', 'https://cliente.exemplo.pt'),
        'admin_email' => env('CMS_ADMIN_EMAIL', 'admin@cliente.exemplo.pt'),
        'locale' => env('CMS_SITE_LOCALE', 'pt_PT'),
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
