<?php

namespace Pcteckserv\CmsCore\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Pcteckserv\CmsCore\Services\Footer\FooterSettingsService;

class CmsFooter extends Component
{
    public function __construct(
        private readonly FooterSettingsService $footerSettings,
    ) {
    }

    public function render(): View
    {
        return view('cms-core::components.footer', [
            'footer' => $this->footerSettings->settings(),
        ]);
    }
}
