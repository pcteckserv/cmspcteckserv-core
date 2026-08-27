<!doctype html>
<html lang="pt-PT">
<head>
    @include('cms-core::public.maintenance.partials.head')
    <style>
        body { display: grid; place-items: stretch; overflow: hidden; }
        .maintenance-hero { height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr) minmax(480px, 640px); overflow: hidden; }
        .maintenance-hero__image { position: relative; background: color-mix(in srgb, var(--cms-maintenance-accent) 28%, #111); height: 100vh; overflow: hidden; }
        .maintenance-hero__image::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent 45%, color-mix(in srgb, var(--cms-maintenance-bg) 72%, transparent)); pointer-events: none; }
        .maintenance-hero__image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .maintenance-hero__content { display: flex; flex-direction: column; justify-content: center; min-width: 0; min-height: 0; overflow: auto; padding: clamp(2.5rem, 5vw, 5rem); background: color-mix(in srgb, var(--cms-maintenance-bg) 96%, #000); }
        .maintenance-hero .maintenance-title { max-width: 10ch; font-size: clamp(2.4rem, 4.2vw, 4.35rem); line-height: 1.04; }
        .maintenance-hero .maintenance-message { max-width: 34rem; }
        @media (max-width: 1199.98px) { body { overflow: auto; } .maintenance-hero { height: auto; min-height: 100vh; grid-template-columns: 1fr; overflow: visible; } .maintenance-hero__image { height: 42vh; min-height: 280px; } .maintenance-hero__image::after { background: linear-gradient(0deg, color-mix(in srgb, var(--cms-maintenance-bg) 72%, transparent), transparent 45%); } .maintenance-hero__content { overflow: visible; } .maintenance-hero .maintenance-title { max-width: 12ch; font-size: clamp(2.3rem, 9vw, 4rem); } }
        @media (max-width: 575.98px) { .maintenance-hero__content { padding: 2rem 1.5rem; } .maintenance-hero .maintenance-title { max-width: 11ch; font-size: clamp(2.2rem, 13vw, 3.4rem); } }
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
