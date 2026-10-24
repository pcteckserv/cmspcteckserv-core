@if($menu)<nav {{ $attributes->class(['cms-menu']) }} aria-label="{{ $menu['name'] }}">@include('cms-core::website.menu-items')</nav>@endif
