export const filterIcons = (icons, query) => {
    const terms = query.trim().toLocaleLowerCase('pt-PT').split(/\s+/).filter(Boolean);
    return Object.entries(icons).filter(([name, icon]) => {
        const text = `${name} ${icon.label}`.toLocaleLowerCase('pt-PT');
        return terms.every((term) => text.includes(term));
    });
};

export const iconPage = (entries, requestedPage, size = 80) => {
    const pages = Math.max(1, Math.ceil(entries.length / size));
    const page = Math.min(Math.max(0, requestedPage), pages - 1);
    return { page, pages, entries: entries.slice(page * size, (page + 1) * size) };
};

export const initializeIconModal = (root = document) => {
    const modal = root.querySelector('[data-cms-icon-modal]');
    const payload = root.querySelector('[data-cms-icon-catalog]');
    if (!modal || !payload || modal.dataset.initialized) return;
    modal.dataset.initialized = 'true';

    // O catálogo contém apenas SVG locais sanitizados, nunca HTML enviado pelo utilizador.
    const libraries = JSON.parse(payload.textContent);
    payload.remove();
    const search = modal.querySelector('[data-cms-icon-search]');
    const grid = modal.querySelector('[data-cms-icon-grid]');
    const status = modal.querySelector('[data-cms-icon-status]');
    const pageLabel = modal.querySelector('[data-cms-icon-page]');
    const previous = modal.querySelector('[data-cms-icon-previous]');
    const next = modal.querySelector('[data-cms-icon-next]');
    const tabs = Array.from(modal.querySelectorAll('[data-cms-icon-library]'));
    let activePicker;
    let library = 'bootstrap';
    let page = 0;
    let previousOverflow = '';

    const render = () => {
        const entries = filterIcons(libraries[library].icons, search.value);
        const result = iconPage(entries, page);
        page = result.page;
        const fragment = document.createDocumentFragment();
        result.entries.forEach(([name, icon]) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'cms-icon-modal__option';
            option.dataset.cmsIconOption = name;
            option.setAttribute('aria-pressed', String(activePicker?.querySelector('[data-cms-icon-picker-input]').value === name));
            const image = document.createElement('span');
            image.innerHTML = icon.svg;
            const label = document.createElement('span');
            label.textContent = icon.label;
            option.append(image, label);
            fragment.append(option);
        });
        grid.replaceChildren(fragment);
        grid.scrollTop = 0;
        status.textContent = entries.length ? `${entries.length} ícones encontrados` : 'Não foram encontrados ícones.';
        pageLabel.textContent = `Página ${page + 1} de ${result.pages}`;
        previous.disabled = page === 0;
        next.disabled = page + 1 === result.pages;
        tabs.forEach((tab) => {
            const selected = tab.dataset.cmsIconLibrary === library;
            tab.classList.toggle('active', selected);
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        modal.querySelector('[role="tabpanel"]').setAttribute('aria-labelledby', `cms-icon-tab-${library}`);
    };

    const select = (name) => {
        if (!activePicker) return;
        const icon = libraries[library].icons[name];
        if (name && !icon) return;
        const input = activePicker.querySelector('[data-cms-icon-picker-input]');
        input.value = name;
        activePicker.querySelector('[data-cms-icon-picker-label]').textContent = icon
            ? `${libraries[library].label} · ${icon.label}` : activePicker.dataset.emptyLabel;
        activePicker.querySelector('[data-cms-icon-picker-preview]').innerHTML = icon?.svg || '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        modal.close();
    };

    // Delegação para suportar campos acrescentados dinamicamente por qualquer plugin.
    root.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-cms-icon-picker-toggle]');
        if (!toggle || modal.open) return;
        activePicker = toggle.closest('[data-cms-icon-picker]');
        const selected = activePicker.querySelector('[data-cms-icon-picker-input]').value;
        library = Object.hasOwn(libraries, selected.split(':')[0]) ? selected.split(':')[0] : 'bootstrap';
        search.value = '';
        const selectedIndex = Object.keys(libraries[library].icons).indexOf(selected);
        page = Math.max(0, Math.floor(selectedIndex / 80));
        render();
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        toggle.setAttribute('aria-expanded', 'true');
        modal.showModal();
        search.focus();
    });
    modal.addEventListener('close', () => {
        document.body.style.overflow = previousOverflow;
        const toggle = activePicker?.querySelector('[data-cms-icon-picker-toggle]');
        toggle?.setAttribute('aria-expanded', 'false');
        toggle?.focus();
        activePicker = null;
    });
    modal.addEventListener('click', (event) => {
        if (event.target.closest('[data-cms-icon-close]')) modal.close();
        if (event.target.closest('[data-cms-icon-clear]')) select('');
        const option = event.target.closest('[data-cms-icon-option]');
        if (option) select(option.dataset.cmsIconOption);
        if (event.target === modal) {
            const bounds = modal.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) modal.close();
        }
    });
    search.addEventListener('input', () => { page = 0; render(); });
    previous.addEventListener('click', () => { page--; render(); previous.focus(); });
    next.addEventListener('click', () => { page++; render(); next.focus(); });
    const activateTab = (tab) => {
        library = tab.dataset.cmsIconLibrary;
        page = 0;
        render();
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab));
        tab.addEventListener('keydown', (event) => {
            const positions = { ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index + tabs.length - 1) % tabs.length, Home: 0, End: tabs.length - 1 };
            if (!(event.key in positions)) return;
            event.preventDefault();
            const target = tabs[positions[event.key]];
            activateTab(target);
            target.focus();
        });
    });
};

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initializeIconModal());
    else initializeIconModal();
    document.addEventListener('cms:icon-picker:init', () => initializeIconModal());
}
