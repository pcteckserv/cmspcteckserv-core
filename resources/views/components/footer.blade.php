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
                height: var(--cms-footer-logo-height);
                max-width: var(--cms-footer-logo-max-width);
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

@include('cms-core::components.partials.footer-markup', ['footer' => $footer])
