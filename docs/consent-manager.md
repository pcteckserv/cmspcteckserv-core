# Consent Manager

O Consent Manager é um módulo reutilizável do `pcteckserv/cms-core` para gerir consentimento de cookies e tecnologias de tracking em rotas públicas.

## Arquitetura

O módulo vive no Core e expõe o contrato `Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract`. Módulos e plugins devem usar este contrato ou a facade `Pcteckserv\CmsCore\Support\Facades\ConsentManager`, sem aceder diretamente às tabelas internas.

Componentes principais:

- `ConsentManager`: registo de serviços, scripts, assinaturas e publicação da configuração.
- `ConsentKnowledgeBase`: assinaturas conhecidas para sugerir fornecedor, serviço e categoria provável.
- `InjectConsentManager`: middleware global que injeta o banner em respostas HTML públicas.
- `ConsentScanner`: scanner com fallback HTTP/HTML para scripts, iframes e storage; pode ser substituído por uma implementação Playwright sem alterar o backoffice.
- `RunConsentScanJob`: execução assíncrona de análises.

## Configuração e publicação

O backoffice fica em `Admin > Consentimentos`.

Fluxo recomendado:

1. editar textos, categorias ou serviços;
2. guardar alterações;
3. publicar;
4. decidir se a alteração exige novo consentimento.

Quando é publicado com novo consentimento, a versão aumenta e browsers com versão antiga voltam a ver o banner.

## Categorias

As categorias iniciais são:

- `necessary`: Necessários, obrigatórios;
- `preferences`: Preferências;
- `analytics`: Estatística;
- `marketing`: Marketing.

Podem ser editadas no backoffice. O Core não assume que estas serão as únicas categorias.

## API para plugins

```php
use Pcteckserv\CmsCore\Support\Facades\ConsentManager;

ConsentManager::registerService([
    'key' => 'plugin-statistics',
    'name' => 'Estatísticas do plugin',
    'provider' => 'Fornecedor',
    'category' => 'analytics',
    'cookies' => ['plugin_stats_*'],
    'domains' => ['analytics.exemplo.com'],
    'source' => 'plugin',
]);
```

```php
ConsentManager::registerKnowledgeSignature([
    'service_key' => 'meta-pixel',
    'service_name' => 'Meta Pixel',
    'provider' => 'Meta',
    'category' => 'marketing',
    'confidence' => 98,
    'patterns' => ['_fbp', 'connect.facebook.net', 'facebook.com/tr'],
    'reason' => 'Assinatura conhecida de Meta Pixel.',
]);
```

No frontend, scripts devem ser carregados apenas depois de consentimento:

```js
if (window.CmsConsent.hasConsent('analytics')) {
    window.CmsConsent.loadScript('https://www.googletagmanager.com/gtag/js?id=G-XXXX', 'analytics');
}
```

## Scanner

Comandos disponíveis:

```bash
php artisan consent:scan
php artisan consent:scan --url=/contactos
php artisan consent:status
php artisan consent:knowledge
```

O scanner descobre rotas públicas GET e exclui `/admin`, `/login`, `/api`, endpoints internos e ficheiros. URLs adicionais podem ser introduzidas no backoffice.

Tecnologias desconhecidas ficam com:

- categoria por definir;
- confiança baixa;
- estado `requires_review`;
- `requires_consent=true`.

Isto garante que desconhecido não é tratado como necessário por defeito.

## Cache

A configuração publicada é guardada em cache com a chave `cms_consent_published_config`. Alterações administrativas invalidam a cache. O frontend recebe apenas a configuração publicada e compacta.

## Eventos

Eventos disponíveis para integração:

- `ConsentGranted`
- `ConsentRevoked`
- `ConsentUpdated`
- `ConsentVersionChanged`
- `ConsentScanCompleted`
- `ConsentTechnologyDetected`
- `ConsentServiceDetected`

## Fallback

Se um scanner Playwright/Chromium não estiver disponível, o banner e a gestão manual continuam funcionais. O scanner atual faz análise parcial de HTML e pode ser substituído por uma implementação headless real mantendo o mesmo contrato de uso.

## Resolução de problemas

Se o banner não aparecer, confirmar:

- a rota é pública e devolve HTML;
- existem categorias ativas;
- `banner_enabled` está ativo;
- a configuração foi publicada;
- a resposta contém `</body>` ou permite anexar conteúdo HTML.
