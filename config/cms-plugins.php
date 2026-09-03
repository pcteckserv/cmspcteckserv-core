<?php

return [
    'enabled' => env('CMS_PLUGINS_ENABLED', true),

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
