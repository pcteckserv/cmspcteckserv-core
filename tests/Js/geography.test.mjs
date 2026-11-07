import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const source = await readFile(new URL('../../resources/js/geography.js', import.meta.url), 'utf8');
const { fold, regionChoices, initGeography } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const regions = [
    { code: 'PT-20', name: 'Região Autónoma dos Açores', aliases: ['Azores', 'Açores', '20'] },
    { code: 'PT-30', name: 'Madeira', aliases: ['Madeira', '30'] },
];

for (const payloadId of ['cms-geography-data', 'shop-geography-data']) {
    test(`o formulário usa ${payloadId} e aceita texto quando não há regiões`, () => {
        const originalDocument = globalThis.document;
        const originalOption = globalThis.Option;
        const form = {};
        const field = (tag, value = '') => ({
            tag, value, form, dataset: {}, attributes: [], options: [],
            setAttribute(name, value) {
                this.attributes = this.attributes.filter((attribute) => attribute.name !== name);
                this.attributes.push({ name, value });
                if (name === 'data-geo-part') this.dataset.geoPart = value;
                if (name === 'data-geo-scope') this.dataset.geoScope = value;
                if (name === 'data-geo-empty-label') this.dataset.geoEmptyLabel = value;
            },
            removeAttribute(name) { this.attributes = this.attributes.filter((attribute) => attribute.name !== name); },
            add(option) { this.options.push(option); },
            addEventListener(name, callback) { this[name] = callback; },
            replaceWith(replacement) { region = replacement; },
            dispatchEvent() {},
        });
        const country = field('select', 'PT');
        country.setAttribute('data-geo-part', 'country');
        country.setAttribute('data-geo-scope', 'address');
        let region = field('input', 'PT-20');
        region.setAttribute('name', 'region');
        region.setAttribute('data-geo-part', 'region');
        region.setAttribute('data-geo-scope', 'address');
        region.setAttribute('data-geo-empty-label', 'Não indicada');
        globalThis.Option = class { constructor(text, value) { this.text = text; this.value = value; } };
        globalThis.document = {
            getElementById: (id) => id === payloadId ? { textContent: JSON.stringify({ regions: { PT: regions } }) } : null,
            querySelectorAll: () => [country, region],
            createElement: (tag) => field(tag),
        };
        try {
            initGeography({ querySelectorAll: () => [country] });
            assert.equal(region.tag, 'select');
            assert.equal(region.value, 'PT-20');
            assert.equal(region.options[1].text, 'Região Autónoma dos Açores');
            assert.equal(region.options[0].text, 'Não indicada');
            country.value = 'ES';
            country.change();
            assert.equal(region.tag, 'input');
            assert.equal(region.value, '');
            assert.equal(region.attributes.find((attribute) => attribute.name === 'name').value, 'region');
        } finally {
            globalThis.document = originalDocument;
            globalThis.Option = originalOption;
        }
    });
}

test('pesquisa por nome ignora acentos e capitalização', () => {
    assert.equal(fold('AÇORES'), fold('acores'));
    assert.equal(fold('São Tomé'), 'sao tome');
});

test('a edição preserva valores antigos sem alterar silenciosamente o identificador', () => {
    assert.deepEqual(regionChoices(regions, 'AZORES')[0], ['AZORES', 'Região Autónoma dos Açores']);
    assert.equal(regionChoices(regions, 'PT-20').length, 2);
    assert.deepEqual(regionChoices(regions, 'Antiga')[0], ['Antiga', 'Antiga (valor anterior)']);
});

test('aliases ambíguos e regiões desconhecidas permanecem explícitos', () => {
    const ambiguous = [...regions, { code: 'PT-99', name: 'Outra', aliases: ['Azores'] }];
    assert.deepEqual(regionChoices(ambiguous, 'Azores')[0], ['Azores', 'Azores (valor anterior)']);
    assert.deepEqual(regionChoices([], 'Lisboa'), [['Lisboa', 'Lisboa (valor anterior)']]);
});
