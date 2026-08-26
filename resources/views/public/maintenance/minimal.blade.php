<!doctype html>
<html lang="pt-PT">
<head>
    @include('cms-core::public.maintenance.partials.head')
    <style>
        body { display: grid; place-items: center; padding: 2rem; background: color-mix(in srgb, var(--cms-maintenance-bg) 92%, #fff); }
        .maintenance-card { width: min(760px, 100%); text-align: center; padding: clamp(2rem, 6vw, 5rem) 1rem; }
        .maintenance-message { margin-inline: auto; }
        .maintenance-access { margin-inline: auto; text-align: left; }
        .maintenance-countdown { justify-content: center; }
    </style>
</head>
<body>
    <main class="maintenance-card">
        @if ($maintenance['show_logo'] && $maintenance['logo_url'])
            <img class="maintenance-logo" src="{{ $maintenance['logo_url'] }}" alt="{{ $maintenance['site_title'] }}">
        @endif
        <div class="maintenance-eyebrow mt-4">Modo de manutenção</div>
        <h1 class="maintenance-title">{{ $maintenance['title'] }}</h1>
        <p class="maintenance-message">{{ $maintenance['message'] }}</p>
        @if ($maintenance['secondary_text'])
            <p class="maintenance-message">{{ $maintenance['secondary_text'] }}</p>
        @endif
        @if ($maintenance['end_at'])
            <p class="maintenance-meta">Regresso previsto: {{ $maintenance['end_at']->timezone($maintenance['timezone'])->format('d/m/Y H:i') }}</p>
        @endif
        @include('cms-core::public.maintenance.partials.countdown')
        @include('cms-core::public.maintenance.partials.access-form')
    </main>
    @if ($maintenance['show_footer'])
        <footer class="maintenance-footer">&copy; {{ now()->year }} {{ $maintenance['site_title'] }}</footer>
    @endif
</body>
</html>
