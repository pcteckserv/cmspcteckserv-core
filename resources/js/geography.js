export function fold(value) {
    return String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-PT');
}

export function regionChoices(regions, current = '') {
    const choices = regions.map((region) => [region.code, region.name]);
    if (current && !choices.some(([code]) => code === current)) {
        const matches = regions.filter((region) => [region.code, ...region.aliases].some((alias) => fold(alias) === fold(current)));
        choices.unshift([current, matches.length === 1 ? matches[0].name : `${current} (valor anterior)`]);
    }
    return choices;
}

export function initGeography(root = document) {
    const payload = document.getElementById('cms-geography-data') || document.getElementById('shop-geography-data');
    if (!payload) return;
    const data = JSON.parse(payload.textContent);
    const controls = () => [...document.querySelectorAll('[data-geo-part]')];
    const updateRegion = (country, reset) => {
        const region = controls().find((field) => field.dataset.geoPart === 'region' && field.dataset.geoScope === country.dataset.geoScope && field.form === country.form);
        if (!region) return;
        const current = reset ? '' : region.value;
        const regions = data.regions[country.value] || [];
        const replacement = document.createElement(regions.length ? 'select' : 'input');
        [...region.attributes].forEach((attribute) => replacement.setAttribute(attribute.name, attribute.value));
        if (regions.length) {
            replacement.removeAttribute('maxlength');
            replacement.add(new Option(region.dataset.geoEmptyLabel || 'Todas as regiões / não indicada', ''));
            regionChoices(regions, current).forEach(([code, name]) => replacement.add(new Option(name, code)));
        } else {
            replacement.setAttribute('maxlength', '80');
        }
        replacement.value = current;
        region.replaceWith(replacement);
        if (reset) replacement.dispatchEvent(new Event('change', { bubbles: true }));
    };
    root.querySelectorAll('[data-geo-part="country"]').forEach((country) => {
        if (country.dataset.geoReady) return;
        country.dataset.geoReady = 'true';
        country.addEventListener('change', () => updateRegion(country, true));
        updateRegion(country, false);
    });
}

export function initLocationRows(root = document) {
    root.querySelectorAll('[data-geo-rows]').forEach((collection) => {
        if (collection.dataset.geoReady) return;
        collection.dataset.geoReady = 'true';
        const add = collection.querySelector('[data-geo-add]');
        add.hidden = false;
        const coverage = document.getElementById(collection.dataset.geoCoverage || '');
        const syncCoverage = () => {
            const world = coverage?.value === 'world';
            collection.hidden = world;
            collection.querySelectorAll('input, select, button').forEach((control) => {
                if (!control.dataset.geoDisabled) control.dataset.geoDisabled = control.disabled ? 'yes' : 'no';
                control.disabled = world || control.dataset.geoDisabled === 'yes';
            });
        };
        if (coverage) {
            coverage.addEventListener('change', syncCoverage);
            syncCoverage();
        }
        collection.querySelectorAll('[data-geo-remove]').forEach((button) => { button.hidden = false; });
        collection.addEventListener('click', (event) => {
            if (event.target.closest('[data-geo-add]')) {
                if (collection.querySelectorAll('[data-geo-row]').length >= Number(collection.dataset.geoLimit || 100)) return;
                const template = collection.querySelector('template');
                const holder = document.createElement('template');
                holder.innerHTML = template.innerHTML.replaceAll('__LOCATION__', crypto.randomUUID().replaceAll('-', ''));
                collection.querySelector('[data-geo-list]').append(holder.content.cloneNode(true));
                collection.querySelectorAll('[data-geo-remove]').forEach((button) => { button.hidden = false; });
                initGeography(collection);
                collection.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (event.target.closest('[data-geo-remove]')) {
                event.target.closest('[data-geo-row]').remove();
                collection.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });
}

if (typeof document !== 'undefined') {
    initGeography();
    initLocationRows();
    new MutationObserver((mutations) => {
        if (mutations.some((mutation) => [...mutation.addedNodes].some((node) => node.nodeType === 1 && (node.matches('[data-geo-field]') || node.querySelector('[data-geo-field]'))))) {
            initGeography();
        }
    }).observe(document.body, { childList: true, subtree: true });
}
