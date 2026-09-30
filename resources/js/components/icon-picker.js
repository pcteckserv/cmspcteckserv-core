const initializeIconPicker = (picker) => {
    if (picker.dataset.cmsIconPickerInitialized) {
        return;
    }

    picker.dataset.cmsIconPickerInitialized = 'true';
    const input = picker.querySelector('[data-cms-icon-picker-input]');
    const toggle = picker.querySelector('[data-cms-icon-picker-toggle]');
    const label = picker.querySelector('[data-cms-icon-picker-label]');
    const preview = picker.querySelector('[data-cms-icon-picker-preview]');
    const options = picker.querySelector('[data-cms-icon-picker-options]');

    if (! input || ! toggle || ! label || ! preview || ! options) {
        return;
    }

    const close = () => {
        options.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        options.hidden = ! options.hidden;
        toggle.setAttribute('aria-expanded', options.hidden ? 'false' : 'true');
    });

    options.querySelectorAll('[data-cms-icon-option]').forEach((option) => {
        option.addEventListener('click', () => {
            input.value = option.dataset.cmsIconOption;
            label.textContent = option.dataset.label;
            preview.replaceChildren(...Array.from(option.querySelectorAll('svg')).map((icon) => icon.cloneNode(true)));
            options.querySelectorAll('[data-cms-icon-option]').forEach((item) => {
                item.setAttribute('aria-selected', item === option ? 'true' : 'false');
            });
            input.dispatchEvent(new Event('change', { bubbles: true }));
            close();
        });
    });

    document.addEventListener('click', (event) => {
        if (! picker.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ! options.hidden) {
            close();
            toggle.focus();
        }
    });
};

const initializeIconPickers = (root = document) => {
    root.querySelectorAll('[data-cms-icon-picker]').forEach(initializeIconPicker);
};

initializeIconPickers();
document.addEventListener('cms:icon-picker:init', (event) => {
    initializeIconPickers(event.target instanceof Element ? event.target : document);
});
