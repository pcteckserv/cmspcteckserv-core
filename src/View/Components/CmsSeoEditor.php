<?php

namespace Pcteckserv\CmsCore\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CmsSeoEditor extends Component
{
    public function __construct(public mixed $model = null, public string $prefix = 'seo')
    {
    }

    public function render(): View
    {
        return view('cms-core::components.seo-editor', [
            'seoMeta' => $this->model && method_exists($this->model, 'seo') ? $this->model->seo : null,
        ]);
    }
}
