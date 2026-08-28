<?php

namespace Pcteckserv\CmsCore\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Pcteckserv\CmsCore\Seo\Services\SeoManager;

class CmsSeo extends Component
{
    public function __construct(public mixed $model = null)
    {
    }

    public function render(): View
    {
        return view('cms-core::components.seo', [
            'seo' => app(SeoManager::class)->for($this->model),
        ]);
    }
}
