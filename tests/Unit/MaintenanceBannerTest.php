<?php

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Pcteckserv\CmsCore\Http\Middleware\HandleCmsMaintenanceMode;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MaintenanceBannerTest extends TestCase
{
    public function test_restore_button_has_only_an_accessible_icon_on_the_right(): void
    {
        $previousContainer = Container::getInstance();
        $previousFacadeApplication = Facade::getFacadeApplication();
        $container = new Container();
        $urls = $this->createMock(UrlGenerator::class);
        $urls->method('route')->willReturn('/admin/maintenance');
        $gate = $this->createMock(GateContract::class);
        $gate->method('allows')->willReturn(false);
        $container->instance('url', $urls);
        $container->instance(GateContract::class, $gate);

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstance(GateContract::class);

        try {
            $reflection = new ReflectionClass(HandleCmsMaintenanceMode::class);
            $middleware = $reflection->newInstanceWithoutConstructor();
            $html = $reflection->getMethod('adminBanner')->invoke($middleware);
            $document = new DOMDocument();
            $document->loadHTML('<meta charset="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);
            $button = $xpath->query('//button[@id="cms-maintenance-admin-banner-show"]')->item(0);

            $this->assertNotNull($button);
            $this->assertSame('', trim($button->textContent));
            $this->assertSame(1, $xpath->query('.//svg[@aria-hidden="true"]', $button)->length);
            $this->assertSame('Mostrar aviso de manutenção', $button->getAttribute('aria-label'));
            $this->assertSame('Mostrar aviso de manutenção', $button->getAttribute('title'));
            $this->assertStringContainsString('right:8px', $button->getAttribute('style'));
            $this->assertStringNotContainsString('left:', $button->getAttribute('style'));
            $this->assertTrue($button->hasAttribute('hidden'));
        } finally {
            Facade::clearResolvedInstance(GateContract::class);
            Facade::setFacadeApplication($previousFacadeApplication);
            Container::setInstance($previousContainer);
        }
    }
}
