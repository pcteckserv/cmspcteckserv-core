<?php

namespace Pcteckserv\CmsCore\View\Components;

use Illuminate\View\Component;
use Pcteckserv\CmsCore\Support\Website;

class CmsMenu extends Component
{
    public function __construct(public ?string $slug = null, public ?string $location = null) {}

    public function render()
    {
        $website = app(Website::class);
        $menu = $this->slug ? $website->menu($this->slug) : collect($website->menus())->firstWhere('location', $this->location);

        return view('cms-core::components.website-menu', ['menu' => $menu, 'items' => $menu ? $website->menuTree($menu) : []]);
    }
}
