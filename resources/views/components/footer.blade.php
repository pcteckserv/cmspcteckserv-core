@if ($footer['enabled'])
    @once
        <style>
            .cms-public-footer {
                background: var(--cms-footer-background);
                color: var(--cms-footer-text);
                padding: var(--cms-footer-padding-y) var(--cms-footer-padding-x);
            }

            .cms-public-footer__inner {
                align-items: center;
                display: flex;
                gap: 1rem;
                justify-content: space-between;
                margin: 0 auto;
                max-width: var(--cms-footer-max-width);
                width: 100%;
            }

            .cms-public-footer__copyright,
            .cms-public-footer__credit {
                margin: 0;
            }

            .cms-public-footer__credit {
                align-items: center;
                color: var(--cms-footer-secondary-text);
                display: inline-flex;
                gap: .5rem;
                text-decoration: none;
            }

            .cms-public-footer__credit:hover {
                color: var(--cms-footer-secondary-text);
                text-decoration: underline;
            }

            .cms-public-footer__logo {
                display: block;
                height: 18px;
                max-width: 140px;
                object-fit: contain;
                width: auto;
            }

            .cms-public-footer__fallback {
                font-weight: 700;
                letter-spacing: .04em;
            }

            @media (max-width: 767.98px) {
                .cms-public-footer__inner {
                    align-items: flex-start;
                    flex-direction: column;
                }
            }
        </style>
    @endonce

    <footer
        class="cms-public-footer"
        style="--cms-footer-background: {{ $footer['background_color'] }}; --cms-footer-text: {{ $footer['text_color'] }}; --cms-footer-secondary-text: {{ $footer['secondary_text_color'] }}; --cms-footer-padding-y: {{ $footer['padding_y'] }}px; --cms-footer-padding-x: {{ $footer['padding_x'] }}px; --cms-footer-max-width: {{ $footer['max_width'] }}px;"
    >
        <div class="cms-public-footer__inner">
            <p class="cms-public-footer__copyright">
                &copy; {{ $footer['year'] }}. {{ $footer['site_title'] }} - {{ $footer['copyright_text'] }}
            </p>

            @if ($footer['show_pcteckserv_credit'])
                <a class="cms-public-footer__credit" href="{{ $footer['pcteckserv_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $footer['credit_text'] }} PCTECKSERV">
                    <span>{{ $footer['credit_text'] }}</span>
                    @if ($footer['pcteckserv_logo_url'])
                        <img class="cms-public-footer__logo" src="{{ $footer['pcteckserv_logo_url'] }}" alt="PCTECKSERV">
                    @else
                        <span class="cms-public-footer__fallback">PCTECKSERV</span>
                    @endif
                </a>
            @endif
        </div>
    </footer>
@endif
