<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>{{ $maintenance['title'] }} | {{ $maintenance['site_title'] }}</title>
@if ($maintenance['site_icon_url'])
    <link rel="icon" href="{{ $maintenance['site_icon_url'] }}">
@endif
<style>
    :root {
        --cms-maintenance-bg: {{ $maintenance['background_color'] }};
        --cms-maintenance-text: {{ $maintenance['text_color'] }};
        --cms-maintenance-accent: {{ $maintenance['accent_color'] }};
        --cms-maintenance-button: {{ $maintenance['button_color'] }};
        --cms-maintenance-logo-max-width: {{ $maintenance['logo_max_width'] }};
        --cms-maintenance-logo-max-height: {{ $maintenance['logo_max_height'] }};
    }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--cms-maintenance-bg); color: var(--cms-maintenance-text); }
    a { color: inherit; }
    .maintenance-logo { max-width: var(--cms-maintenance-logo-max-width); max-height: var(--cms-maintenance-logo-max-height); object-fit: contain; }
    .maintenance-eyebrow { color: var(--cms-maintenance-accent); font-weight: 700; letter-spacing: 0; text-transform: uppercase; font-size: .82rem; }
    .maintenance-title { font-size: clamp(2rem, 7vw, 4.8rem); line-height: 1.02; letter-spacing: 0; margin: .75rem 0 1rem; }
    .maintenance-message { font-size: clamp(1rem, 2vw, 1.2rem); line-height: 1.65; opacity: .86; max-width: 680px; }
    .maintenance-meta { margin-top: 1.25rem; font-weight: 600; }
    .maintenance-access { margin-top: 2rem; max-width: 520px; }
    .maintenance-access label { display: block; margin-bottom: .65rem; font-weight: 700; }
    .maintenance-access__row { display: flex; gap: .75rem; }
    .maintenance-access input { min-width: 0; flex: 1; border: 1px solid rgba(255,255,255,.22); border-radius: .5rem; padding: .95rem 1rem; font: inherit; font-size: 16px; }
    .maintenance-access button { border: 0; border-radius: .5rem; padding: .95rem 1.15rem; background: var(--cms-maintenance-button); color: #fff; font-weight: 700; cursor: pointer; }
    .maintenance-error { color: #ffb4b4; margin: .75rem 0 0; }
    .maintenance-countdown { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .maintenance-countdown span { min-width: 74px; padding: .75rem; border: 1px solid rgba(255,255,255,.18); border-radius: .5rem; text-align: center; font-size: 1.35rem; font-weight: 800; }
    .maintenance-footer { position: fixed; left: 0; right: 0; bottom: 0; padding: 1rem; text-align: center; font-size: .9rem; opacity: .72; }
    @media (max-width: 575.98px) { .maintenance-access__row { flex-direction: column; } .maintenance-footer { position: static; } }
</style>
