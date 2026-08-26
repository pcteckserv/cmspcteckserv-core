import './bootstrap';

const uploadZone = document.querySelector('[data-cms-media-upload-url]');

if (uploadZone) {
    const input = uploadZone.querySelector('[data-cms-media-file-input]');
    const selectButton = uploadZone.querySelector('[data-cms-media-select-files]');
    const results = uploadZone.querySelector('[data-cms-media-upload-results]');
    const uploadUrl = uploadZone.dataset.cmsMediaUploadUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const uploadFiles = async (files) => {
        if (!files.length) {
            return;
        }

        const formData = new FormData();
        [...files].forEach((file) => formData.append('files[]', file));

        results.innerHTML = [...files]
            .map((file) => `<div class="cms-media-upload-row"><span>${file.name}</span><span>A enviar...</span></div>`)
            .join('');

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Não foi possível enviar os ficheiros.');
            }

            results.innerHTML = data.items
                .map((item) => `<div class="cms-media-upload-row"><span>${item.name}</span><span class="text-success">Enviado</span></div>`)
                .join('');

            window.setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger mb-0">${error.message}</div>`;
        }
    };

    selectButton?.addEventListener('click', () => input.click());
    input?.addEventListener('change', (event) => uploadFiles(event.target.files));

    uploadZone.addEventListener('dragover', (event) => {
        event.preventDefault();
        uploadZone.classList.add('is-dragging');
    });

    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('is-dragging'));

    uploadZone.addEventListener('drop', (event) => {
        event.preventDefault();
        uploadZone.classList.remove('is-dragging');
        uploadFiles(event.dataTransfer.files);
    });
}

document.querySelectorAll('[data-cms-media-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        const url = button.dataset.cmsMediaCopy;

        try {
            await navigator.clipboard.writeText(url);
            const original = button.textContent;
            button.textContent = 'URL copiado';
            window.setTimeout(() => {
                button.textContent = original;
            }, 1400);
        } catch {
            window.prompt('Copie o URL do ficheiro:', url);
        }
    });
});

window.cmsMediaPicker = function cmsMediaPicker(options = {}) {
    const params = new URLSearchParams();
    if (options.type) {
        params.set('type', options.type);
    }

    window.open(`/admin/media?${params.toString()}`, 'cms-media-picker', 'width=1200,height=800');
};
