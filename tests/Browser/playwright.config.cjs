const {defineConfig}=require('@playwright/test');
module.exports=defineConfig({testDir:'.',testMatch:'*.spec.cjs',workers:1,use:{baseURL:'http://127.0.0.1:9878',headless:true},webServer:{command:'node server.cjs',url:'http://127.0.0.1:9878/health',reuseExistingServer:false}});
