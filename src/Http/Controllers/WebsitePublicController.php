<?php

namespace Pcteckserv\CmsCore\Http\Controllers;

use Pcteckserv\CmsCore\Support\Website;

class WebsitePublicController
{
    public function policy(string $slug, Website $website)
    {
        $key = array_search($slug, Website::SLUGS, true);
        abort_unless($key !== false && trim($website->policy($key)) !== '', 404);

        return view('cms-core::website.policy', ['title' => Website::POLICIES[$key], 'text' => $website->policy($key)]);
    }

    public function menu(string $slug, Website $website)
    {
        $menu = $website->menu($slug);
        abort_unless($menu, 404);

        return view('cms-core::website.menu-page', ['menu' => $menu, 'items' => $website->menuTree($menu)]);
    }

    public function css(Website $website)
    {
        return response($website->get('css', ''), 200, ['Content-Type' => 'text/css; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-cache']);
    }
}
