<!doctype html>
<html lang="pt-PT">
<head>
    @include('cms-core::public.maintenance.partials.head')
    <style>
        body { display: grid; place-items: center; overflow-x: hidden; padding: 2rem; }
        .maintenance-modern { width: min(1080px, 100%); position: relative; padding: clamp(2rem, 7vw, 6rem); border: 1px solid rgba(255,255,255,.14); border-radius: .75rem; background: linear-gradient(135deg, rgba(255,255,255,.08), rgba(255,255,255,.02)); }
        .maintenance-modern::before { content: ""; position: absolute; inset: 1rem; border: 1px solid color-mix(in srgb, var(--cms-maintenance-accent) 48%, transparent); pointer-events: none; }
        .maintenance-progress { width: min(420px, 100%); height: .45rem; background: rgba(255,255,255,.12); border-radius: 999px; overflow: hidden; margin-top: 1.75rem; }
        .maintenance-progress span { display: block; width: 68%; height: 100%; background: var(--cms-maintenance-accent); }
        @media (prefers-reduced-motion: no-preference) { .maintenance-modern { animation: maintenanceIn .55s ease-out both; } @keyframes maintenanceIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } } }
    </style>
</head>
<body>
    <main class="maintenance-modern">
        @if ($maintenance['show_logo'] && $maintenance['logo_url'])
            <img class="maintenance-logo" src="{{ $maintenance['logo_url'] }}" alt="{{ $maintenance['site_title'] }}">
        @endif
        <p class="maintenance-eyebrow mt-4">Intervenção técnica em curso</p>
        <h1 class="maintenance-title">{{ $maintenance['title'] }}</h1>
        <p class="maintenance-message">{{ $maintenance['message'] }}</p>
        @if ($maintenance['secondary_text'])
            <p class="maintenance-message">{{ $maintenance['secondary_text'] }}</p>
        @endif
        <div class="maintenance-progress" aria-hidden="true"><span></span></div>
        @include('cms-core::public.maintenance.partials.countdown')
        @include('cms-core::public.maintenance.partials.access-form')
    </main>
    @if ($maintenance['show_footer'])
        <footer class="maintenance-footer">&copy; {{ now()->year }} {{ $maintenance['site_title'] }}</footer>
    @endif
</body>
</html>
