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

## Sitemap automático

O sitemap descobre automaticamente as rotas GET estáticas do grupo `web`, usando o domínio definido em `seo_base_url`. Não executa pedidos às páginas. As rotas com parâmetros (incluindo opcionais), fallback, redirecionamentos, outros domínios, assets e endpoints técnicos são excluídas. Rotas com middleware de autenticação, autorização ou outro middleware não reconhecido como público também são excluídas.

Pode excluir uma página com `->defaults('sitemap', false)`. A configuração `cms-core.sitemap` permite desativar a descoberta, definir caminhos excluídos e reconhecer middleware adicional como público. Adicione apenas middleware que não limite o acesso à página.

As URLs de conteúdos dinâmicos continuam a ser fornecidas por `SitemapProviderInterface`. As integrações existentes são preservadas e têm prioridade sobre as URLs descobertas, sem duplicação de URLs iguais. A cache dura uma hora; alterações nas rotas descobertas ou no domínio regeneram-na automaticamente, incluindo a substituição da antiga cache vazia. Para alterações nos conteúdos dinâmicos, utilize `SitemapGenerator::clearCache()`.

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
