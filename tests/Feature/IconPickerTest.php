<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\Support\IconCatalog;
use Tests\TestCase;

class IconPickerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app('view')->replaceNamespace('cms-core', dirname(__DIR__, 2).'/resources/views');
    }

    public function test_bibliotecas_completas_e_identificadores_antigos_continuam_disponiveis(): void
    {
        $libraries = IconCatalog::libraries();
        $this->assertSame(['bootstrap', 'fontawesome'], array_keys($libraries));
        $this->assertGreaterThan(2000, count($libraries['bootstrap']['icons']));
        $this->assertGreaterThan(2800, count($libraries['fontawesome']['icons']));
        $options = IconCatalog::options();
        $this->assertSame('Confirmação', $options['check']);
        foreach (['check', 'bootstrap:house', 'fontawesome:solid/house', 'fontawesome:regular/heart', 'fontawesome:brands/github'] as $name) {
            $this->assertTrue(Validator::make(['icon' => $name], ['icon' => ['required', Rule::in(array_keys($options))]])->passes());
            $html = view('cms-core::components.icons.catalog', ['name' => $name])->render();
            $this->assertStringContainsString('<svg', $html);
            $this->assertStringContainsString('<', $html);
            $this->assertStringNotContainsString('@case', $html);
        }
    }

    public function test_identificadores_arbitrarios_nao_sao_aceites_nem_apresentados(): void
    {
        foreach (['bootstrap:../../secret', 'fontawesome:solid/nonexistent', '<script>alert(1)</script>', 'https://example.com/icon.svg'] as $name) {
            $this->assertNull(IconCatalog::icon($name));
            $this->assertTrue(Validator::make(['icon' => $name], ['icon' => [Rule::in(array_keys(IconCatalog::options()))]])->fails());
            $html = view('cms-core::components.icons.catalog', ['name' => $name])->render();
            $this->assertStringNotContainsString($name, $html);
            $this->assertStringNotContainsString('<path', $html);
        }
    }

    public function test_varios_campos_partilham_um_modal_fora_dos_templates_dinamicos(): void
    {
        $html = Blade::render(<<<'BLADE'
            <template><x-cms-icon-picker name="dynamic[__INDEX__][icon]" /></template>
            <x-cms-icon-picker name="icon" label="Ícone" value="bootstrap:house" />
            <x-cms-icon-picker name="other" value="fontawesome:regular/heart" />
            @stack('cms-icon-picker-modal')
            BLADE);

        $this->assertSame(1, substr_count($html, '<dialog '));
        $this->assertSame(1, substr_count($html, 'data-cms-icon-catalog>'));
        $this->assertGreaterThan(strpos($html, '</template>'), strpos($html, '<dialog '));
        $this->assertStringContainsString('aria-haspopup="dialog"', $html);
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('Bootstrap Icons', $html);
        $this->assertStringContainsString('Font Awesome Free', $html);
        $this->assertStringContainsString('value="bootstrap:house"', $html);
        $this->assertStringContainsString('value="fontawesome:regular/heart"', $html);
        $this->assertStringNotContainsString('cms-icon-picker__options', $html);
    }

    public function test_labels_sao_escapadas_e_valor_invalido_nao_e_selecionado(): void
    {
        $html = Blade::render('<x-cms-icon-picker name="icon" :label="$label" :value="$value" />', [
            'label' => '<img src=x onerror=alert(1)>',
            'value' => '<script>alert(1)</script>',
        ]);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('value=""', $html);
    }
}
