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
}
