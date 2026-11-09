import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const source = await readFile(new URL('../../resources/js/components/icon-picker.js', import.meta.url), 'utf8');
const { filterIcons, iconPage } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const bootstrap = JSON.parse(await readFile(new URL('../../resources/icons/bootstrap.json', import.meta.url), 'utf8'));
const fontawesome = JSON.parse(await readFile(new URL('../../resources/icons/fontawesome.json', import.meta.url), 'utf8'));

test('a pesquisa filtra todos os termos e ignora maiúsculas e espaços', () => {
    const entries = filterIcons(bootstrap.icons, '  HOUSE   FILL ');
    assert.ok(entries.length > 0);
    assert.ok(entries.every(([name]) => name.includes('house') && name.includes('fill')));
    assert.equal(filterIcons(bootstrap.icons, 'inexistente-xyz').length, 0);
    assert.equal(filterIcons(bootstrap.icons, ' ').length, 2078);
});

test('bibliotecas e estilos têm identificadores independentes', () => {
    assert.ok(filterIcons(fontawesome.icons, 'solid/house').some(([name]) => name === 'fontawesome:solid/house'));
    assert.ok(fontawesome.icons['fontawesome:regular/heart']);
    assert.ok(fontawesome.icons['fontawesome:brands/github']);
    assert.equal(filterIcons(bootstrap.icons, 'fontawesome:').length, 0);
});

test('paginação limita a grelha a 80 ícones e permite chegar ao último', () => {
    const entries = filterIcons(bootstrap.icons, '');
    assert.equal(iconPage(entries, 0).entries.length, 80);
    assert.equal(iconPage(entries, -1).page, 0);
    const last = iconPage(entries, 999);
    assert.equal(last.page, 25);
    assert.equal(last.entries.at(-1)[0], entries.at(-1)[0]);
    assert.deepEqual(iconPage([], 5), { page: 0, pages: 1, entries: [] });
});
