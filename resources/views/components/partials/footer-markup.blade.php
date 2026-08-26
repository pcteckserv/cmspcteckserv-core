@php
    $forceRender = $forceRender ?? false;
    $extraClass = $extraClass ?? '';
    $attributes = $attributes ?? '';
@endphp

@if ($footer['enabled'] || $forceRender)
    <footer
        class="cms-public-footer {{ $extraClass }}"
        style="--cms-footer-background: {{ $footer['background_color'] }}; --cms-footer-text: {{ $footer['text_color'] }}; --cms-footer-secondary-text: {{ $footer['secondary_text_color'] }}; --cms-footer-padding-y: {{ $footer['padding_y'] }}; --cms-footer-padding-x: {{ $footer['padding_x'] }}; --cms-footer-max-width: {{ $footer['max_width'] }}; --cms-footer-logo-height: {{ $footer['pcteckserv_logo_height'] }}; --cms-footer-logo-max-width: {{ $footer['pcteckserv_logo_max_width'] }};"
        {!! $attributes !!}
    >
        <div class="cms-public-footer__inner">
            <p class="cms-public-footer__copyright">
                &copy; {{ $footer['year'] }}. {{ $footer['site_title'] }} - {{ $footer['copyright_text'] }}
            </p>

            <a
                class="cms-public-footer__credit"
                href="{{ $footer['pcteckserv_url'] }}"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="{{ $footer['credit_text'] }} PCTECKSERV"
                @if (! $footer['show_pcteckserv_credit']) hidden @endif
                data-cms-footer-preview-credit
            >
                <span data-cms-footer-preview-credit-text>{{ $footer['credit_text'] }}</span>
                <span class="cms-public-footer__brand" data-cms-footer-preview-brand>
                    @if ($footer['pcteckserv_logo_url'])
                        <img class="cms-public-footer__logo" src="{{ $footer['pcteckserv_logo_url'] }}" alt="PCTECKSERV">
                    @else
                        <span class="cms-public-footer__fallback">PCTECKSERV</span>
                    @endif
                </span>
            </a>
        </div>
    </footer>
@endif
