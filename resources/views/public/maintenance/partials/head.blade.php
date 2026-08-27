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
    body { margin: 0; min-height: 100vh; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--cms-maintenance-bg); color: var(--cms-maintenance-text); text-rendering: optimizeLegibility; }
    a { color: inherit; }
    .maintenance-logo { display: block; max-width: var(--cms-maintenance-logo-max-width); max-height: var(--cms-maintenance-logo-max-height); object-fit: contain; }
    .maintenance-eyebrow { color: var(--cms-maintenance-accent); font-weight: 800; letter-spacing: 0; text-transform: uppercase; font-size: .78rem; }
    .maintenance-title { font-size: clamp(2.35rem, 7vw, 5.4rem); line-height: .98; letter-spacing: 0; margin: .85rem 0 1rem; text-wrap: balance; }
    .maintenance-message { font-size: clamp(1rem, 1.7vw, 1.18rem); line-height: 1.75; opacity: .84; max-width: 680px; }
    .maintenance-meta { display: inline-flex; margin-top: 1.25rem; padding: .55rem .8rem; border: 1px solid color-mix(in srgb, var(--cms-maintenance-text) 16%, transparent); border-radius: .5rem; font-weight: 700; background: color-mix(in srgb, var(--cms-maintenance-text) 7%, transparent); }
    .maintenance-access { margin-top: 2rem; max-width: 520px; }
    .maintenance-access label { display: block; margin-bottom: .65rem; font-weight: 800; }
    .maintenance-access__row { display: flex; gap: .75rem; }
    .maintenance-access input { min-width: 0; flex: 1; border: 1px solid color-mix(in srgb, var(--cms-maintenance-text) 20%, transparent); border-radius: .5rem; padding: .95rem 1rem; background: color-mix(in srgb, var(--cms-maintenance-bg) 72%, #fff); color: var(--cms-maintenance-text); font: inherit; font-size: 16px; outline: none; }
    .maintenance-access input:focus { border-color: var(--cms-maintenance-accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--cms-maintenance-accent) 24%, transparent); }
    .maintenance-access button { border: 0; border-radius: .5rem; padding: .95rem 1.15rem; background: var(--cms-maintenance-button); color: #fff; font-weight: 800; cursor: pointer; box-shadow: 0 16px 34px color-mix(in srgb, var(--cms-maintenance-button) 28%, transparent); }
    .maintenance-error { color: #ffb4b4; margin: .75rem 0 0; }
    .maintenance-countdown { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .maintenance-countdown span { min-width: 76px; padding: .8rem .75rem; border: 1px solid color-mix(in srgb, var(--cms-maintenance-text) 16%, transparent); border-radius: .5rem; background: color-mix(in srgb, var(--cms-maintenance-text) 7%, transparent); text-align: center; font-size: 1.35rem; font-weight: 900; }
    .maintenance-footer { position: fixed; left: 0; right: 0; bottom: 0; padding: 1rem; text-align: center; font-size: .9rem; opacity: .68; }
    @media (max-width: 575.98px) { .maintenance-access__row { flex-direction: column; } .maintenance-footer { position: static; } }
</style>
