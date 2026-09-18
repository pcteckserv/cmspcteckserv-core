<?php

return [
    'enabled' => env('CMS_PLUGINS_ENABLED', true),
    'repository_url' => env('CMS_PLUGINS_REPOSITORY_URL', 'https://github.com/pcteckserv/cmspcteckserv-plugins.git'),
    'repository_branch' => env('CMS_PLUGINS_REPOSITORY_BRANCH'),
    'metadata_file' => 'cms-plugin.json',

    /*
    |--------------------------------------------------------------------------
    | Catálogo de plugins
    |--------------------------------------------------------------------------
    |
    | Cada entrada representa uma package Composer que pode ser gerida pelo CMS.
    | O código do plugin continua fora do core; o core apenas gere o estado.
    |
    */
    'plugins' => [
        // 'blog' => [
        //     'name' => 'pcteckserv/cms-blog',
        //     'package' => 'pcteckserv/cms-blog',
        //     'label' => 'Blog',
        //     'description' => 'Gestão de artigos e categorias.',
        //     'provider' => Pcteckserv\CmsBlog\BlogServiceProvider::class,
        //     'repository' => env('CMS_BLOG_REPOSITORY_URL'),
        // ],
    ],
];
