const http=require('http'),fs=require('fs'),path=require('path');
const fixtures=path.resolve(__dirname,'../Standalone/.fixtures');
const assets=process.env.CMS_BROWSER_ASSETS||path.resolve(__dirname,'../../../novosite/public/build');
const routes={'/admin/perfil':'profile','/seguranca/web':'security','/cliente':'customer-account','/cliente/perfil':'customer-profile','/seguranca/shop_customer':'customer-security','/cliente/moradas':'customer-addresses'};
http.createServer((req,res)=>{
 const url=new URL(req.url,'http://127.0.0.1:9878');
 if(url.pathname==='/health'){res.end('ok');return;}
 let file,type='text/html';
 if(url.pathname.startsWith('/admin/website/'))file=path.join(fixtures,'website-'+url.pathname.split('/').pop()+'.html');
 else if(url.pathname==='/cms-account-form.js'){file=path.resolve(__dirname,'../../resources/js/account-form.js');type='application/javascript';}
 else if(url.pathname==='/politica-de-privacidade')file=path.join(fixtures,'website-policy.html');
 else if(routes[url.pathname])file=path.join(fixtures,routes[url.pathname]+'.html');
 else if(url.pathname.startsWith('/build/assets/')){const name=path.basename(url.pathname);if(!/^[\w.-]+$/.test(name)){res.writeHead(400);res.end();return;}file=path.join(assets,'assets',name);type=name.endsWith('.css')?'text/css':name.endsWith('.js')?'application/javascript':'font/woff2';}
 if(!file||!fs.existsSync(file)){res.writeHead(404);res.end();return;}
 res.writeHead(200,{'Content-Type':type+'; charset=utf-8','Cache-Control':'no-store'});res.end(fs.readFileSync(file));
}).listen(9878,'127.0.0.1');
