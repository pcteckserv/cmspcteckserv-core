const {test,expect}=require('@playwright/test');
test('menu branches move, duplicate, prevent cycles and undo removals on mobile',async({page},info)=>{
 await page.goto('/admin/website/menus');
 const rows=page.locator('[data-menu-item]');
 for(const name of ['Empresa','Contacto','Loja']){await page.locator('[data-menu-add]').click();await rows.last().locator('[data-item-label]').fill(name);await rows.last().locator('[data-item-url]').fill('/'+name.toLowerCase());}
 const root=await rows.first().locator('[data-item-id]').inputValue();
 await rows.nth(1).locator('[data-menu-indent]').click();await expect(rows.nth(1).locator('[data-item-parent]')).toHaveValue(root);
 await expect(rows.first().locator('[data-item-parent] option')).toHaveCount(2);
 await rows.first().locator('[data-menu-down]').click();await expect(rows.nth(0).locator('[data-item-label]')).toHaveValue('Loja');await expect(rows.nth(1).locator('[data-item-label]')).toHaveValue('Empresa');await expect(rows.nth(2).locator('[data-item-label]')).toHaveValue('Contacto');
 await rows.nth(1).locator('[data-menu-duplicate]').click();await expect(rows).toHaveCount(5);
 const copy=await rows.nth(3).locator('[data-item-id]').inputValue();await expect(rows.nth(4).locator('[data-item-parent]')).toHaveValue(copy);
 await rows.nth(3).locator('[data-menu-remove]').click();await expect(rows).toHaveCount(4);await expect(rows.last().locator('[data-item-parent]')).toHaveValue('');
 await page.locator('[data-menu-undo]').click();await expect(rows).toHaveCount(5);await expect(rows.last().locator('[data-item-parent]')).toHaveValue(copy);
 await page.locator('[data-menu-select-all]').check();await page.locator('[data-menu-remove-selected]').click();await expect(rows).toHaveCount(0);await page.locator('[data-menu-undo]').click();await expect(rows).toHaveCount(5);
 await page.setViewportSize({width:390,height:844});expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);await page.screenshot({path:info.outputPath('menu-mobile.png'),fullPage:true});
});
test('menu names generate identifiers while preserving manual changes',async({page})=>{await page.goto('/admin/website/menus');await page.locator('[data-menu-name]').fill('Ligações úteis');await expect(page.locator('[data-menu-slug]')).toHaveValue('ligacoes-uteis');await page.locator('[data-menu-slug]').fill('manual');await page.locator('[data-menu-name]').fill('Outro nome');await expect(page.locator('[data-menu-slug]')).toHaveValue('manual');});
