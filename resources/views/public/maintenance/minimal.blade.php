<!doctype html>
<html lang="pt-PT">
<head>
    @include('cms-core::public.maintenance.partials.head')
    <style>
        body { display: grid; place-items: center; padding: 2rem; background: radial-gradient(circle at top, color-mix(in srgb, var(--cms-maintenance-accent) 18%, transparent), transparent 34rem), var(--cms-maintenance-bg); }
        .maintenance-card { width: min(780px, 100%); text-align: center; padding: clamp(2.5rem, 7vw, 5.5rem) clamp(1rem, 4vw, 2rem); }
        .maintenance-logo { margin-inline: auto; }
        .maintenance-message { margin-inline: auto; }
        .maintenance-access { margin-inline: auto; text-align: left; }
        .maintenance-countdown { justify-content: center; }
    </style>
</head>
<body>
    <main class="maintenance-card">
        @if ($maintenance['logo_url'])
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
