<!doctype html>
<html lang="pt-PT">
<head>
    @include('cms-core::public.maintenance.partials.head')
    <style>
        body { display: grid; place-items: stretch; }
        .maintenance-hero { min-height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr) minmax(360px, 560px); }
        .maintenance-hero__image { background: color-mix(in srgb, var(--cms-maintenance-accent) 28%, #111); min-height: 100%; }
        .maintenance-hero__image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .maintenance-hero__content { display: flex; flex-direction: column; justify-content: center; padding: clamp(2rem, 5vw, 5rem); }
        @media (max-width: 767.98px) { .maintenance-hero { grid-template-columns: 1fr; } .maintenance-hero__image { min-height: 38vh; } }
    </style>
</head>
<body>
    <main class="maintenance-hero">
        <div class="maintenance-hero__image" aria-hidden="true">
            @if ($maintenance['hero_url'])
                <img src="{{ $maintenance['hero_url'] }}" alt="">
            @endif
        </div>
        <section class="maintenance-hero__content">
            @if ($maintenance['show_logo'] && $maintenance['logo_url'])
                <img class="maintenance-logo" src="{{ $maintenance['logo_url'] }}" alt="{{ $maintenance['site_title'] }}">
            @endif
            <p class="maintenance-eyebrow mt-4">Voltamos em breve</p>
            <h1 class="maintenance-title">{{ $maintenance['title'] }}</h1>
            <p class="maintenance-message">{{ $maintenance['message'] }}</p>
            @if ($maintenance['secondary_text'])
                <p class="maintenance-message">{{ $maintenance['secondary_text'] }}</p>
            @endif
            @include('cms-core::public.maintenance.partials.countdown')
            @include('cms-core::public.maintenance.partials.access-form')
        </section>
    </main>
</body>
</html>
