<?php

namespace Pcteckserv\CmsCore\Seo\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Seo\Services\SitemapGenerator;
use Pcteckserv\CmsCore\Support\SiteOptions;

class SitemapController extends Controller
{
    public function __invoke(SitemapGenerator $generator, SiteOptions $siteOptions): Response
    {
        abort_unless(filter_var($siteOptions->get('seo_generate_sitemap', true), FILTER_VALIDATE_BOOLEAN), 404);

        return response($generator->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
