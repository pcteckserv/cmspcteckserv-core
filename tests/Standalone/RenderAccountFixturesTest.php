<?php

namespace Pcteckserv\CmsCore\Tests;

use Illuminate\Support\Facades\URL;
use Pcteckserv\CmsCore\Http\Middleware\InjectAdminShortcut;
use Pcteckserv\CmsCore\Http\Middleware\InjectConsentManager;
use Pcteckserv\CmsCore\Security\AccountFactor;
use Pcteckserv\CmsCore\Security\TwoFactor;

require_once __DIR__.'/SharedCustomersTest.php';

class RenderAccountFixturesTest extends SharedCustomersTest
{
    public function test_export_account_browser_fixtures(): void
    {
        config(['app.name' => 'Empresa & Filhos']);
        URL::forceRootUrl('http://127.0.0.1:9878');
        $this->withoutMiddleware([InjectConsentManager::class, InjectAdminShortcut::class]);
        $dir = __DIR__.'/.fixtures';
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $manifestPath = getenv('CMS_BROWSER_MANIFEST') ?: dirname(__DIR__, 3).'/novosite/public/build/manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $assets = '<link rel="stylesheet" href="/build/'.$manifest['vendor/pcteckserv/cms-core/resources/css/admin.scss']['file'].'"><script type="module" src="/build/'.$manifest['vendor/pcteckserv/cms-core/resources/js/admin.js']['file'].'"></script>';
        $staff = $this->manager();
        $this->actingAs($staff, 'web');
        AccountFactor::create(['guard' => 'web', 'account_id' => $staff->id, 'secret' => app(TwoFactor::class)->secret()]);
        foreach (['profile' => '/admin/perfil', 'security' => '/seguranca/web'] as $file => $url) {
            file_put_contents($dir.'/'.$file.'.html', str_replace('</head>', $assets.'</head>', $this->get($url)->assertOk()->getContent()));
        }
        $customer = $this->customer();
        $this->actingAs($customer, 'shop_customer');
        AccountFactor::create(['guard' => 'shop_customer', 'account_id' => $customer->id, 'secret' => app(TwoFactor::class)->secret()]);
        foreach (['customer-profile' => '/cliente/perfil', 'customer-account' => '/cliente', 'customer-security' => '/seguranca/shop_customer', 'customer-addresses' => '/cliente/moradas'] as $file => $url) {
            file_put_contents($dir.'/'.$file.'.html', $this->get($url)->assertOk()->getContent());
        }
    }
}
