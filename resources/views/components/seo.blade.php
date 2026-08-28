@php($seo = $seo ?? app(\Pcteckserv\CmsCore\Seo\Services\SeoManager::class)->for($model ?? null))
<title>{{ $seo->title }}</title>
@if ($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif
@if ($seo->canonicalUrl)
    <link rel="canonical" href="{{ $seo->canonicalUrl }}">
@endif
<meta name="robots" content="{{ $seo->robotsContent() }}">
@if (app(\Pcteckserv\CmsCore\Support\SiteOptions::class)->get('seo_generate_open_graph', true))
    <meta property="og:title" content="{{ $seo->ogTitle }}">
    @if ($seo->ogDescription)
        <meta property="og:description" content="{{ $seo->ogDescription }}">
    @endif
    @if ($seo->ogImage)
        <meta property="og:image" content="{{ $seo->ogImage }}">
    @endif
    @if ($seo->canonicalUrl)
        <meta property="og:url" content="{{ $seo->canonicalUrl }}">
    @endif
    <meta property="og:type" content="{{ $seo->ogType }}">
@endif
@if (app(\Pcteckserv\CmsCore\Support\SiteOptions::class)->get('seo_generate_twitter_cards', true))
    <meta name="twitter:card" content="{{ $seo->twitterCard }}">
    <meta name="twitter:title" content="{{ $seo->twitterTitle }}">
    @if ($seo->twitterDescription)
        <meta name="twitter:description" content="{{ $seo->twitterDescription }}">
    @endif
    @if ($seo->twitterImage)
        <meta name="twitter:image" content="{{ $seo->twitterImage }}">
    @endif
@endif
@foreach ($seo->schema as $schema)
    <script type="application/ld+json">@json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
@endforeach
