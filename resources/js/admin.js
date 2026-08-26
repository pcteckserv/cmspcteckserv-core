import './bootstrap';

document.querySelectorAll('[data-cms-help-widget]').forEach((widget) => {
    const toggle = widget.querySelector('[data-cms-help-toggle]');
    const panel = widget.querySelector('[data-cms-help-panel]');
    const close = widget.querySelector('[data-cms-help-close]');

    if (! toggle || ! panel) {
        return;
    }

    const setOpen = (isOpen) => {
        panel.hidden = ! isOpen;
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setOpen(panel.hidden);
    });

    close?.addEventListener('click', () => {
        setOpen(false);
        toggle.focus();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });

    document.addEventListener('click', (event) => {
        if (! widget.contains(event.target)) {
            setOpen(false);
        }
    });
});

document.querySelectorAll('[data-cms-media-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        const url = button.dataset.cmsMediaCopy;

        if (! url) {
            return;
        }

        try {
            await navigator.clipboard.writeText(url);
        } catch (error) {
            return;
        }

        const originalText = button.textContent;
        if (originalText?.trim()) {
            button.textContent = 'Copiado';
            window.setTimeout(() => {
                button.textContent = originalText;
            }, 1600);
        }
    });
});

document.querySelectorAll('[data-cms-media-upload-url]').forEach((dropzone) => {
    const input = dropzone.querySelector('[data-cms-media-file-input]');
    const selectButton = dropzone.querySelector('[data-cms-media-select-files]');
    const openButtons = document.querySelectorAll('[data-cms-media-open-upload]');
    const results = dropzone.querySelector('[data-cms-media-upload-results]');
    const uploadUrl = dropzone.dataset.cmsMediaUploadUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (! input || ! selectButton || ! results || ! uploadUrl || ! csrfToken) {
        return;
    }

    const renderResult = (message, variant = 'info') => {
        const item = document.createElement('div');
        item.className = `cms-media-upload-result text-${variant === 'error' ? 'danger' : 'secondary'}`;
        item.textContent = message;
        results.prepend(item);
    };

    const uploadFiles = async (files) => {
        if (! files.length) {
            return;
        }

        const formData = new FormData();
        Array.from(files).forEach((file) => formData.append('files[]', file));

        dropzone.classList.remove('is-dragging');
        renderResult(`A carregar ${files.length} ficheiro(s)...`);

        try {
            const response = await fetch(uploadUrl, {
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                method: 'POST',
            });

            if (! response.ok) {
                const payload = await response.json().catch(() => ({}));
                const firstError = Object.values(payload.errors ?? {})?.[0]?.[0];
                throw new Error(firstError || payload.message || 'Não foi possível carregar os ficheiros.');
            }

            const payload = await response.json();
            const count = payload.items?.length ?? files.length;
            renderResult(`${count} ficheiro(s) carregado(s). A atualizar a biblioteca...`, 'success');
            window.setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            renderResult(error.message || 'Não foi possível carregar os ficheiros.', 'error');
        } finally {
            input.value = '';
        }
    };

    selectButton.addEventListener('click', () => input.click());
    openButtons.forEach((button) => {
        button.addEventListener('click', () => input.click());
    });

    input.addEventListener('change', () => uploadFiles(input.files));

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        });
    });

    dropzone.addEventListener('drop', (event) => {
        uploadFiles(event.dataTransfer?.files ?? []);
    });
});

document.querySelectorAll('[data-cms-footer-preview]').forEach((preview) => {
    const enabled = document.querySelector('#footer_enabled');
    const title = document.querySelector('#site_title');
    const copyrightText = document.querySelector('#footer_copyright_text');
    const showCredit = document.querySelector('#footer_show_pcteckserv_credit');
    const creditText = document.querySelector('#footer_credit_text');
    const creditUrl = document.querySelector('#footer_pcteckserv_url');
    const logoMediaId = document.querySelector('#footer_pcteckserv_logo_media_id');
    const logoPath = document.querySelector('#footer_pcteckserv_logo_path');
    const logoScale = document.querySelector('#footer_pcteckserv_logo_scale');
    const logoScaleOutput = document.querySelector('[data-cms-footer-logo-scale-output]');
    const backgroundColor = document.querySelector('#footer_background_color');
    const textColor = document.querySelector('#footer_text_color');
    const secondaryTextColor = document.querySelector('#footer_secondary_text_color');
    const paddingY = document.querySelector('#footer_padding_y');
    const paddingX = document.querySelector('#footer_padding_x');
    const maxWidth = document.querySelector('#footer_max_width');
    const copyright = preview.querySelector('.cms-public-footer__copyright');
    const credit = preview.querySelector('[data-cms-footer-preview-credit]');
    const creditTextPreview = preview.querySelector('[data-cms-footer-preview-credit-text]');
    const brand = preview.querySelector('[data-cms-footer-preview-brand]');
    const year = new Date().getFullYear();
    let selectedLogoUrl = preview.querySelector('.cms-public-footer__logo')?.getAttribute('src') || '';
    const cssSizePattern = /^(?:0|\d+(?:\.\d+)?(?:px|%|rem|em|vh|vw|vmin|vmax|ch|ex|cm|mm|in|pt|pc)|(?:auto|min-content|max-content|fit-content)|(?:calc|clamp|min|max)\([A-Za-z0-9\s+\-*\/().,%]+\))$/;

    const cssSize = (input, fallback) => {
        const value = input?.value?.trim() || '';

        return cssSizePattern.test(value) ? value : fallback;
    };

    const fallbackLogoUrl = () => {
        const path = logoPath?.value?.trim().replace(/^\/+/, '');

        if (! path) {
            return '';
        }

        return path.startsWith('vendor/') ? `/${path}` : `/storage/${path}`;
    };

    const proportionalLogoSize = (basePixels) => {
        const scale = Math.min(250, Math.max(25, Number.parseInt(logoScale?.value || '100', 10) || 100));

        return `${Math.round(basePixels * scale) / 100}px`;
    };

    const renderLogo = (imageUrl = '') => {
        if (! brand) {
            return;
        }

        brand.replaceChildren();

        if (imageUrl) {
            const image = document.createElement('img');
            image.className = 'cms-public-footer__logo';
            image.src = imageUrl;
            image.alt = 'PCTECKSERV';
            brand.append(image);
            return;
        }

        const fallback = document.createElement('span');
        fallback.className = 'cms-public-footer__fallback';
        fallback.textContent = 'PCTECKSERV';
        brand.append(fallback);
    };

    const updatePreview = () => {
        preview.hidden = enabled ? ! enabled.checked : false;
        preview.style.setProperty('--cms-footer-background', backgroundColor?.value || '#0c0c0c');
        preview.style.setProperty('--cms-footer-text', textColor?.value || '#ffffff');
        preview.style.setProperty('--cms-footer-secondary-text', secondaryTextColor?.value || '#ffffff');
        preview.style.setProperty('--cms-footer-padding-y', cssSize(paddingY, '28px'));
        preview.style.setProperty('--cms-footer-padding-x', cssSize(paddingX, '24px'));
        preview.style.setProperty('--cms-footer-max-width', cssSize(maxWidth, '1320px'));
        preview.style.setProperty('--cms-footer-logo-height', proportionalLogoSize(18));
        preview.style.setProperty('--cms-footer-logo-max-width', proportionalLogoSize(140));

        if (logoScaleOutput) {
            logoScaleOutput.value = `${logoScale?.value || 100}%`;
            logoScaleOutput.textContent = `${logoScale?.value || 100}%`;
        }

        if (credit) {
            credit.hidden = showCredit ? ! showCredit.checked : false;
            credit.href = creditUrl?.value || 'https://pcteckserv.com';
        }

        if (creditTextPreview) {
            creditTextPreview.textContent = creditText?.value || 'Desenvolvido por';
        }

        if (copyright) {
            copyright.textContent = `© ${year}. ${title?.value || 'CMS PCTECK'} - ${copyrightText?.value || 'Todos os direitos reservados'}`;
        }

        if (! logoMediaId?.value) {
            renderLogo(fallbackLogoUrl());
        }
    };

    document.addEventListener('cms:media-picker-selected', (event) => {
        if (event.detail?.inputName !== 'footer_pcteckserv_logo_media_id') {
            return;
        }

        selectedLogoUrl = event.detail?.item?.original_url || event.detail?.item?.url || '';
        renderLogo(selectedLogoUrl);
    });

    [enabled, title, copyrightText, showCredit, creditText, creditUrl, logoPath, logoScale, backgroundColor, textColor, secondaryTextColor, paddingY, paddingX, maxWidth].forEach((input) => {
        input?.addEventListener('input', updatePreview);
        input?.addEventListener('change', updatePreview);
    });

    updatePreview();
});

{
    const modal = document.querySelector('[data-cms-media-picker-modal]');
    const pickers = document.querySelectorAll('[data-cms-media-picker]');
    const close = modal?.querySelector('[data-cms-media-picker-close]');
    const search = modal?.querySelector('[data-cms-media-picker-search]');
    const file = modal?.querySelector('[data-cms-media-picker-file]');
    const grid = modal?.querySelector('[data-cms-media-picker-grid]');
    const status = modal?.querySelector('[data-cms-media-picker-status]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let activePicker = null;

    if (modal && pickers.length && close && search && file && grid && status) {
        const setStatus = (message, isError = false) => {
            status.textContent = message;
            status.classList.toggle('text-danger', isError);
            status.classList.toggle('text-secondary', ! isError);
        };

        const renderItems = (items) => {
            grid.replaceChildren();

            if (! items.length) {
                setStatus('Não foram encontradas imagens.');
                return;
            }

            setStatus('');

            items.forEach((item) => {
                const button = document.createElement('button');
                const image = document.createElement('img');
                const label = document.createElement('span');

                button.className = 'cms-media-picker__item';
                button.type = 'button';
                image.src = item.thumbnail_url || item.url;
                image.alt = item.alt_text || item.name || 'Imagem';
                label.textContent = item.name || `Media #${item.id}`;

                button.append(image, label);
                button.addEventListener('click', () => {
                    const input = activePicker?.querySelector('[data-cms-media-picker-input]');
                    const display = activePicker?.querySelector('[data-cms-media-picker-display]');
                    const selected = activePicker?.querySelector('[data-cms-media-picker-selected]');

                    if (! input || ! display || ! selected) {
                        return;
                    }

                    input.value = item.id;
                    display.value = label.textContent;
                    selected.textContent = `Selecionado: ${label.textContent}`;
                    activePicker.dispatchEvent(new CustomEvent('cms:media-picker-selected', {
                        bubbles: true,
                        detail: {
                            inputName: input.name,
                            item,
                        },
                    }));
                    modal.hidden = true;
                });

                grid.append(button);
            });
        };

        const loadItems = async () => {
            if (! activePicker?.dataset.libraryUrl) {
                return;
            }

            const url = new URL(activePicker.dataset.libraryUrl, window.location.origin);
            url.searchParams.set('q', search.value || '');

            setStatus('A carregar biblioteca...');

            try {
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });

                if (! response.ok) {
                    throw new Error('Não foi possível carregar a biblioteca.');
                }

                const payload = await response.json();
                renderItems(payload.items || []);
            } catch (error) {
                setStatus(error.message || 'Não foi possível carregar a biblioteca.', true);
            }
        };

        const uploadFile = async () => {
            if (! activePicker?.dataset.uploadUrl || ! file.files.length) {
                return;
            }

            if (! csrfToken) {
                setStatus('Não foi possível validar a sessão. Atualize a página e tente novamente.', true);
                return;
            }

            const formData = new FormData();
            formData.append('files[]', file.files[0]);
            setStatus('A carregar imagem...');

            try {
                const response = await fetch(activePicker.dataset.uploadUrl, {
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    method: 'POST',
                });

                if (! response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    const firstError = Object.values(payload.errors ?? {})?.[0]?.[0];
                    throw new Error(firstError || payload.message || 'Não foi possível carregar a imagem.');
                }

                const payload = await response.json();
                renderItems(payload.items || []);
            } catch (error) {
                setStatus(error.message || 'Não foi possível carregar a imagem.', true);
            } finally {
                file.value = '';
            }
        };

        pickers.forEach((picker) => {
            picker.querySelector('[data-cms-media-picker-open]')?.addEventListener('click', () => {
                activePicker = picker;
                search.value = '';
                modal.hidden = false;
                loadItems();
                search.focus();
            });
        });

        close.addEventListener('click', () => {
            modal.hidden = true;
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.hidden = true;
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                modal.hidden = true;
            }
        });

        search.addEventListener('input', () => {
            window.clearTimeout(search.dataset.cmsMediaPickerTimeout);
            search.dataset.cmsMediaPickerTimeout = window.setTimeout(loadItems, 250);
        });

        file.addEventListener('change', uploadFile);
    }
}
