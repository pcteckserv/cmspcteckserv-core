@once
<style>
.cms-public-header{background:#fff;color:#24343d;border-bottom:1px solid #e1e6e9;padding:18px 24px;font:16px/1.5 system-ui,sans-serif}.cms-public-header__inner{max-width:1320px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:24px}.cms-public-header__brand{font-size:20px;font-weight:750;text-decoration:none;color:inherit;overflow-wrap:anywhere}.cms-public-header a:focus-visible,.cms-public-footer a:focus-visible{outline:2px solid currentColor;outline-offset:4px}@media(max-width:650px){.cms-public-header__inner{align-items:flex-start;flex-wrap:wrap}}
</style>
@endonce
<header class="cms-public-header"><div class="cms-public-header__inner"><a class="cms-public-header__brand" href="{{ url('/') }}">{{ app(\Pcteckserv\CmsCore\Support\SiteOptions::class)->get('site_title',config('app.name')) }}</a></div></header>
