@if(($config['enabled'] ?? true) && count($config['categories'] ?? []) > 0)
<div id="cms-consent-root" hidden></div>
<script>
window.CmsConsentConfig = @json($config);
(function () {
    const key = 'cms_consent';
    const config = window.CmsConsentConfig || {};
    const root = document.getElementById('cms-consent-root');
    const stored = readConsent();

    window.CmsConsent = {
        hasConsent(category) {
            const current = readConsent();
            const item = (config.categories || []).find((categoryConfig) => categoryConfig.key === category);
            if (item && item.required) return true;
            return !!(current && current.version === config.version && current.categories && current.categories[category]);
        },
        open: render,
        update(categories) {
            save(categories);
            dispatch(categories);
        },
        loadScript(src, category, attributes) {
            if (! this.hasConsent(category)) return false;
            const script = document.createElement('script');
            script.src = src;
            Object.entries(attributes || {}).forEach(([name, value]) => script.setAttribute(name, value));
            document.head.appendChild(script);
            return true;
        }
    };

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-cms-consent-open]');
        if (trigger) {
            event.preventDefault();
            render();
        }
    });

    if (!stored || stored.version !== config.version) {
        render();
    }

    function readConsent() {
        try { return JSON.parse(localStorage.getItem(key)); } catch (error) { return null; }
    }

    function save(categories) {
        localStorage.setItem(key, JSON.stringify({
            version: config.version,
            timestamp: new Date().toISOString(),
            categories: categories
        }));
        root.hidden = true;
        root.innerHTML = '';
    }

    function defaults(value) {
        return Object.fromEntries((config.categories || []).map((category) => [category.key, category.required ? true : value]));
    }

    function dispatch(categories) {
        window.dispatchEvent(new CustomEvent('cms-consent-updated', { detail: { version: config.version, categories: categories } }));
    }

    function render() {
        const current = readConsent();
        const values = current && current.version === config.version ? current.categories : defaults(false);
        const texts = config.texts || {};
        root.hidden = false;
        root.innerHTML = `
            <div class="cms-consent-backdrop" role="presentation"></div>
            <section class="cms-consent-panel" role="dialog" aria-modal="true" aria-labelledby="cms-consent-title">
                <div class="cms-consent-main">
                    <h2 id="cms-consent-title">${escapeHtml(texts.banner_title || 'Utilização de cookies')}</h2>
                    <p>${escapeHtml(texts.banner_description || '')}</p>
                    <div class="cms-consent-actions">
                        <button type="button" data-action="accept">${escapeHtml(texts.accept_all || 'Aceitar todos')}</button>
                        <button type="button" data-action="reject">${escapeHtml(texts.reject_optional || 'Rejeitar não essenciais')}</button>
                        <button type="button" data-action="customize">${escapeHtml(texts.customize || 'Personalizar')}</button>
                    </div>
                    <form class="cms-consent-preferences" hidden>
                        <h3>${escapeHtml(texts.preferences_title || 'Preferências de cookies')}</h3>
                        <p>${escapeHtml(texts.preferences_description || '')}</p>
                        ${(config.categories || []).map((category) => `
                            <label class="cms-consent-category">
                                <span><strong>${escapeHtml(category.name)}</strong><small>${escapeHtml(category.description || '')}</small></span>
                                <input type="checkbox" name="${escapeAttr(category.key)}" ${values[category.key] ? 'checked' : ''} ${category.required ? 'checked disabled' : ''}>
                            </label>
                        `).join('')}
                        <button type="submit">${escapeHtml(texts.save || 'Guardar preferências')}</button>
                    </form>
                </div>
            </section>`;

        root.querySelector('[data-action="accept"]').addEventListener('click', () => { const categories = defaults(true); save(categories); dispatch(categories); });
        root.querySelector('[data-action="reject"]').addEventListener('click', () => { const categories = defaults(false); save(categories); dispatch(categories); });
        root.querySelector('[data-action="customize"]').addEventListener('click', () => root.querySelector('form').hidden = false);
        root.querySelector('form').addEventListener('submit', function (event) {
            event.preventDefault();
            const categories = defaults(false);
            (config.categories || []).forEach((category) => {
                const input = root.querySelector(`input[name="${CSS.escape(category.key)}"]`);
                categories[category.key] = category.required ? true : !!input?.checked;
            });
            save(categories);
            dispatch(categories);
        });
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
})();
</script>
<style>
.cms-consent-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.38);z-index:9998}.cms-consent-panel{position:fixed;left:1rem;right:1rem;bottom:1rem;z-index:9999;background:#fff;color:#1f2937;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 20px 45px rgba(15,23,42,.24);max-width:760px;margin:auto}.cms-consent-main{padding:1rem}.cms-consent-main h2{font-size:1.25rem;margin:0 0 .5rem}.cms-consent-main h3{font-size:1rem;margin:1rem 0 .25rem}.cms-consent-main p{margin:.25rem 0 .75rem}.cms-consent-actions{display:flex;flex-wrap:wrap;gap:.5rem}.cms-consent-actions button,.cms-consent-preferences button{border:1px solid #111827;background:#111827;color:#fff;border-radius:6px;padding:.55rem .8rem}.cms-consent-actions button:nth-child(2),.cms-consent-actions button:nth-child(3){background:#fff;color:#111827}.cms-consent-category{display:flex;justify-content:space-between;gap:1rem;border-top:1px solid #e5e7eb;padding:.7rem 0}.cms-consent-category small{display:block;color:#4b5563}.cms-consent-category input{width:1.2rem;height:1.2rem}
</style>
@endif
