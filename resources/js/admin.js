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

document.querySelectorAll('.cms-footer-preview').forEach((preview) => {
    const title = document.querySelector('#site_title');
    const copyrightText = document.querySelector('#footer_copyright_text');
    const creditText = document.querySelector('#footer_credit_text');
    const backgroundColor = document.querySelector('#footer_background_color');
    const textColor = document.querySelector('#footer_text_color');
    const secondaryTextColor = document.querySelector('#footer_secondary_text_color');
    const copyright = preview.querySelector('.cms-footer-preview__copyright');
    const credit = preview.querySelector('.cms-footer-preview__credit');
    const year = new Date().getFullYear();

    const updatePreview = () => {
        preview.style.backgroundColor = backgroundColor?.value || '#0c0c0c';
        preview.style.color = textColor?.value || '#ffffff';

        if (credit) {
            credit.style.color = secondaryTextColor?.value || '#ffffff';
            credit.firstChild.textContent = `${creditText?.value || 'Desenvolvido por'} `;
        }

        if (copyright) {
            copyright.textContent = `© ${year}. ${title?.value || 'CMS PCTECK'} - ${copyrightText?.value || 'Todos os direitos reservados'}`;
        }
    };

    [title, copyrightText, creditText, backgroundColor, textColor, secondaryTextColor].forEach((input) => {
        input?.addEventListener('input', updatePreview);
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

    if (modal && pickers.length && close && search && file && grid && status && csrfToken) {
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
                    const selected = activePicker?.querySelector('[data-cms-media-picker-selected]');

                    if (! input || ! selected) {
                        return;
                    }

                    input.value = item.id;
                    selected.textContent = `Selecionado: ${label.textContent}`;
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
