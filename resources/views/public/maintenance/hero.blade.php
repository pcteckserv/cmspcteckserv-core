<!doctype html>
<html lang="pt-PT">
<head>
    @include('cms-core::public.maintenance.partials.head')
    <style>
        body { display: grid; place-items: stretch; overflow: hidden; }
        .maintenance-hero { height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr) minmax(360px, 560px); overflow: hidden; }
        .maintenance-hero__image { position: relative; background: color-mix(in srgb, var(--cms-maintenance-accent) 28%, #111); height: 100vh; overflow: hidden; }
        .maintenance-hero__image::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent 45%, color-mix(in srgb, var(--cms-maintenance-bg) 72%, transparent)); pointer-events: none; }
        .maintenance-hero__image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .maintenance-hero__content { display: flex; flex-direction: column; justify-content: center; min-height: 0; overflow: auto; padding: clamp(2.5rem, 5vw, 5rem); background: color-mix(in srgb, var(--cms-maintenance-bg) 96%, #000); }
        @media (max-width: 767.98px) { body { overflow: auto; } .maintenance-hero { height: auto; min-height: 100vh; grid-template-columns: 1fr; overflow: visible; } .maintenance-hero__image { height: 38vh; min-height: 260px; } .maintenance-hero__image::after { background: linear-gradient(0deg, color-mix(in srgb, var(--cms-maintenance-bg) 72%, transparent), transparent 45%); } }
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
            @if ($maintenance['logo_url'])
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
