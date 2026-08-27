<!doctype html>
<html lang="pt-PT">
<head>
    @include('cms-core::public.maintenance.partials.head')
    <style>
        body { display: grid; place-items: center; overflow-x: hidden; padding: 2rem; background: linear-gradient(145deg, color-mix(in srgb, var(--cms-maintenance-bg) 84%, #000), var(--cms-maintenance-bg)); }
        .maintenance-modern { width: min(1080px, 100%); position: relative; padding: clamp(2.5rem, 7vw, 6rem); border: 1px solid color-mix(in srgb, var(--cms-maintenance-text) 14%, transparent); border-radius: .75rem; background: linear-gradient(135deg, color-mix(in srgb, var(--cms-maintenance-text) 9%, transparent), color-mix(in srgb, var(--cms-maintenance-accent) 8%, transparent)); box-shadow: 0 30px 80px rgba(0,0,0,.28); }
        .maintenance-modern::before { content: ""; position: absolute; inset: 1rem; border: 1px solid color-mix(in srgb, var(--cms-maintenance-accent) 38%, transparent); pointer-events: none; }
        .maintenance-modern > * { position: relative; }
        .maintenance-progress { width: min(420px, 100%); height: .45rem; background: color-mix(in srgb, var(--cms-maintenance-text) 12%, transparent); border-radius: 999px; overflow: hidden; margin-top: 1.75rem; }
        .maintenance-progress span { display: block; width: 68%; height: 100%; background: var(--cms-maintenance-accent); }
        @media (prefers-reduced-motion: no-preference) { .maintenance-modern { animation: maintenanceIn .55s ease-out both; } @keyframes maintenanceIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } } }
    </style>
</head>
<body>
    <main class="maintenance-modern">
        @if ($maintenance['logo_url'])
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
