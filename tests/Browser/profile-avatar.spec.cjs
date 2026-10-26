const {test,expect}=require('@playwright/test');
test('profile avatar is centered, square and opens the original on desktop and mobile',async({page},info)=>{
 await page.route('**/storage/staff-avatars/browser.jpg',r=>r.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="120" height="400"><rect width="120" height="400" fill="#ff7439"/></svg>'}));
 await page.goto('/admin/perfil');const avatar=page.locator('img[alt="Fotografia de perfil"]');await expect(avatar).toBeVisible();await expect(avatar.locator('..')).toHaveAttribute('target','_blank');
 for(const width of [1280,390]){await page.setViewportSize({width,height:844});const geometry=await avatar.evaluate(img=>{const a=img.getBoundingClientRect(),b=img.closest('fieldset').getBoundingClientRect();return {width:a.width,height:a.height,offset:Math.abs(a.x+a.width/2-b.x-b.width/2),fit:getComputedStyle(img).objectFit,center:getComputedStyle(img).objectPosition};});expect(geometry.width).toBe(96);expect(geometry.height).toBe(96);expect(geometry.offset).toBeLessThan(2);expect(geometry.fit).toBe('cover');expect(geometry.center).toBe('50% 50%');}
 await page.screenshot({path:info.outputPath('profile-avatar-mobile.png'),fullPage:true});
});
