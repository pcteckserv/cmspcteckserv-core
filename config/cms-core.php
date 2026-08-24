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
];
