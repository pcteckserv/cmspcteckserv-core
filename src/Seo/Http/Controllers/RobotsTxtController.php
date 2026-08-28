<?php

namespace Pcteckserv\CmsCore\Seo\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Seo\Services\RobotsTxtGenerator;

class RobotsTxtController extends Controller
{
    public function __invoke(RobotsTxtGenerator $generator): Response
    {
        return response($generator->text(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
