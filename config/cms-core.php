<?php

return [
    'user_model' => env('CMS_CORE_USER_MODEL'),
    'super_admin_role' => env('CMS_CORE_SUPER_ADMIN_ROLE', 'core.super_admin'),
    'user_states' => ['active', 'inactive'],
    'users_per_page' => env('CMS_CORE_USERS_PER_PAGE', 15),

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
        'site_icon_url' => env('CMS_SITE_ICON_URL', '/vendor/cms-core/images/favicon.png'),
        'site_url' => env('CMS_SITE_URL', env('APP_URL', 'https://cliente.exemplo.pt')),
        'admin_email' => env('CMS_ADMIN_EMAIL', 'admin@exemplo.pt'),
        'locale' => env('CMS_SITE_LOCALE', 'pt_PT'),
        'smtp_enabled' => env('CMS_SMTP_ENABLED', false),
        'smtp_host' => env('MAIL_HOST', '127.0.0.1'),
        'smtp_port' => env('MAIL_PORT', 2525),
        'smtp_username' => env('MAIL_USERNAME'),
        'smtp_password' => env('MAIL_PASSWORD'),
        'smtp_encryption' => env('CMS_SMTP_ENCRYPTION', env('MAIL_ENCRYPTION')),
        'smtp_from_address' => env('MAIL_FROM_ADDRESS', env('CMS_ADMIN_EMAIL', 'admin@exemplo.pt')),
        'smtp_from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'CMS PCTECK')),
        'footer_enabled' => env('CMS_FOOTER_ENABLED', true),
        'footer_copyright_text' => env('CMS_FOOTER_COPYRIGHT_TEXT', 'Todos os direitos reservados'),
        'footer_background_color' => env('CMS_FOOTER_BACKGROUND_COLOR', '#0C0C0C'),
        'footer_text_color' => env('CMS_FOOTER_TEXT_COLOR', '#FFFFFF'),
        'footer_secondary_text_color' => env('CMS_FOOTER_SECONDARY_TEXT_COLOR', '#FFFFFF'),
        'footer_show_pcteckserv_credit' => env('CMS_FOOTER_SHOW_PCTECKSERV_CREDIT', true),
        'footer_credit_text' => env('CMS_FOOTER_CREDIT_TEXT', 'Desenvolvido por'),
        'footer_pcteckserv_logo_media_id' => env('CMS_FOOTER_PCTECKSERV_LOGO_MEDIA_ID'),
        'footer_pcteckserv_logo_path' => env('CMS_FOOTER_PCTECKSERV_LOGO_PATH', 'cms/media/2026/08/logotipos-pcteckserv-texto.svg'),
        'footer_pcteckserv_url' => env('CMS_FOOTER_PCTECKSERV_URL', 'https://pcteckserv.com'),
        'footer_padding_y' => env('CMS_FOOTER_PADDING_Y', 28),
        'footer_padding_x' => env('CMS_FOOTER_PADDING_X', 24),
        'footer_max_width' => env('CMS_FOOTER_MAX_WIDTH', 1320),
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

    'media' => [
        'disk' => env('CMS_MEDIA_DISK', 'public'),
        'directory' => env('CMS_MEDIA_DIRECTORY', 'cms/media'),
        'max_size' => env('CMS_MEDIA_MAX_SIZE', 10 * 1024),
        'allow_svg' => env('CMS_MEDIA_ALLOW_SVG', false),
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf'],
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
            'application/pdf',
        ],
        'optimization' => [
            'enabled' => env('CMS_MEDIA_OPTIMIZATION_ENABLED', true),
            'webp' => env('CMS_MEDIA_WEBP_ENABLED', true),
            'quality' => env('CMS_MEDIA_WEBP_QUALITY', 82),
            'keep_original' => env('CMS_MEDIA_KEEP_ORIGINAL', true),
            'variants' => [320, 640, 1280, 1920],
            'thumbnail' => [
                'width' => 300,
                'height' => 300,
            ],
        ],
        'size_warnings' => [
            'warning' => 500 * 1024,
            'critical' => 2 * 1024 * 1024,
        ],
        'resolution_warning' => [
            'width' => 2560,
            'height' => 1440,
        ],
        'permissions' => [
            'media.view',
            'media.upload',
            'media.upload-svg',
            'media.edit',
            'media.delete',
            'media.restore',
            'media.force-delete',
            'media.optimize',
        ],
    ],
];
