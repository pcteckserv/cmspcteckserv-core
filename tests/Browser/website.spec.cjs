const {test,expect}=require('@playwright/test');
test('global options expose account and policy tabs with usable embed codes',async({page},info)=>{
 await page.goto('/admin/website/accounts');await expect(page.getByText('Permitir o registo público de clientes',{exact:true})).toBeVisible();
 await page.getByRole('link',{name:'Políticas',exact:true}).click();await page.locator('summary').filter({hasText:'Política de privacidade'}).click();
 const details=page.locator('details').filter({has:page.locator('[name="policies[privacy]"]')});await expect(details.locator('textarea[readonly]')).toHaveValue(/<iframe/);
 await page.setViewportSize({width:390,height:844});await page.screenshot({path:info.outputPath('policies-mobile.png'),fullPage:true});expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
});
