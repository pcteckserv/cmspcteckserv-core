# SEO

O módulo SEO vive no core e expõe uma camada reutilizável para metadata, schemas, sitemap, robots, redirects e auditoria técnica.

## Utilização em modelos

```php
use Pcteckserv\CmsCore\Seo\Concerns\HasSeo;

class Page extends Model
{
    use HasSeo;
}
```

Depois pode resolver SEO com:

```php
$seo = app(\Pcteckserv\CmsCore\Seo\Services\SeoManager::class)->for($page);
```

## Componentes Blade

No frontend:

```blade
<x-cms-seo :model="$page" />
```

Em formulários administrativos:

```blade
<x-cms-seo-editor :model="$page" />
```

O formulário deve persistir o array `seo` validado pelo módulo que usa o componente.

## Extensibilidade

Plugins podem registar integrações no seu service provider:

```php
use Pcteckserv\CmsCore\Seo\Support\Facades\Seo;

Seo::registerSitemapProvider(ProductsSitemapProvider::class);
Seo::registerSchemaProvider(ProductSchemaProvider::class);
Seo::registerAuditRule(ProductSeoRule::class);
Seo::registerTemplateVariable('product_name', fn ($product) => $product?->name);
```

Os contratos principais estão em `Pcteckserv\CmsCore\Seo\Contracts`.

## Segurança

O componente de frontend escapa metadata com Blade e emite JSON-LD com `@json`. Redirects bloqueiam protocolos perigosos como `javascript:` e `data:`. O registo de 404 ignora assets comuns e não guarda IP em claro por defeito.
