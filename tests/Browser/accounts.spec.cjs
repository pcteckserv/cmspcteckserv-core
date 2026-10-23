const {test,expect}=require('@playwright/test'),{PNG}=require('pngjs'),jsQR=require('jsqr');
test('QR codes scan to the correct local TOTP account on desktop and mobile',async({page},info)=>{
 for(const url of ['/seguranca/web','/seguranca/shop_customer']){
  await page.goto(url);
  const qr=page.locator('[data-authenticator-qr]');await expect(qr).toBeVisible();
  const png=PNG.sync.read(await qr.screenshot());const code=jsQR(new Uint8ClampedArray(png.data),png.width,png.height);
  expect(code).toBeTruthy();const uri=new URL(code.data);expect(uri.protocol).toBe('otpauth:');expect(uri.hostname).toBe('totp');
  expect(uri.searchParams.get('issuer')).toBe('Empresa & Filhos');expect(uri.searchParams.get('digits')).toBe('6');expect(uri.searchParams.get('period')).toBe('30');
  await page.getByText('Não consegue ler o QR code?',{exact:true}).click();
  expect(uri.searchParams.get('secret')).toBe((await page.locator('code').first().textContent()).trim());
  await page.setViewportSize({width:390,height:900});
  expect((await page.locator('[data-qr-instructions]').boundingBox()).width).toBeGreaterThan(200);
  expect(await page.evaluate(()=>document.documentElement.scrollHeight)).toBeLessThan(2200);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
  await page.screenshot({path:info.outputPath(url.includes('shop_customer')?'customer-qr-mobile.png':'staff-qr-mobile.png'),fullPage:true});
 }
});
test('staff profile menu opens and provides profile, security and logout',async({page},info)=>{
 await page.goto('/admin/perfil');const menu=page.locator('.cms-profile-menu');
 await menu.locator('summary').click();await expect(menu.getByRole('link',{name:'O meu perfil',exact:true})).toBeVisible();
 await expect(menu.getByRole('link',{name:'Segurança da conta',exact:true})).toBeVisible();await expect(menu.getByRole('button',{name:'Terminar sessão'})).toBeVisible();
 await menu.getByRole('link',{name:'Segurança da conta',exact:true}).click();await expect(page.locator('[data-authenticator-qr]')).toBeVisible();
 await page.setViewportSize({width:390,height:900});await page.goto('/admin/perfil');await page.locator('.cms-profile-menu summary').click();
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);await page.screenshot({path:info.outputPath('staff-profile-mobile.png'),fullPage:true});
});
test('customer profile controls remain in the customer area',async({page})=>{
 await page.setViewportSize({width:390,height:900});await page.goto('/cliente');
 const menu=page.locator('.customer-account-menu');await menu.locator('summary').click();
 await expect(menu.getByRole('link',{name:'O meu perfil',exact:true})).toBeVisible();await expect(menu.getByRole('button',{name:'Terminar sessão'})).toBeVisible();
 await menu.getByRole('link',{name:'O meu perfil',exact:true}).click();await expect(page.getByRole('heading',{name:'O meu perfil'})).toBeVisible();
 await expect(page.locator('[name="name"]')).toHaveValue('Maria');await expect(page.locator('.admin-sidebar')).toHaveCount(0);
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
});
