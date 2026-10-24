const {test,expect}=require('@playwright/test');
test('global options expose account and policy tabs with usable embed codes',async({page},info)=>{
 await page.goto('/admin/website/accounts');await expect(page.getByText('Permitir o registo público de clientes',{exact:true})).toBeVisible();
 await page.getByRole('link',{name:'Políticas',exact:true}).click();await page.locator('summary').filter({hasText:'Política de privacidade'}).click();
 const details=page.locator('details').filter({has:page.locator('[name="policies[privacy]"]')});await expect(details.locator('textarea[readonly]')).toHaveValue(/<iframe/);
 await page.setViewportSize({width:390,height:844});await page.screenshot({path:info.outputPath('policies-mobile.png'),fullPage:true});expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
});
test('menus add links and policies, keep hierarchy and remove items',async({page},info)=>{
 await page.goto('/admin/website/menus');await page.getByRole('button',{name:'Adicionar ligação',exact:true}).click();let items=page.locator('[data-menu-item]');await expect(items).toHaveCount(1);await items.first().locator('[data-item-label]').fill('Sobre');await items.first().locator('[data-item-url]').fill('/sobre');
 await page.locator('[data-policy-choice]').selectOption({label:'Política de privacidade'});await expect(items).toHaveCount(2);await expect(items.nth(1).locator('[data-item-label]')).toHaveValue('Política de privacidade');
 const id=await items.first().locator('[data-item-id]').inputValue();await items.nth(1).locator('[data-item-parent]').selectOption(id);await expect(items.nth(1).locator('[data-item-parent]')).toHaveValue(id);
 await page.setViewportSize({width:390,height:844});await page.screenshot({path:info.outputPath('menus-mobile.png'),fullPage:true});expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
 await items.nth(1).getByRole('button',{name:'Remover',exact:true}).click();await expect(items).toHaveCount(1);
});
