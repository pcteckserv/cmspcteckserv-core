# CMS PCTECKSERV — Core 3

Base principal atualizada a partir de `NOVO/pcteckcms_core` (commit `5a09323`), incluindo a integração genérica validada no NextHome. Mudança major: substitui a linha 2.20 e altera os contratos de clientes/autenticação.

O Core fornece administração, utilizadores, perfis, permissões, 2FA, geografia, conteúdos, SEO e gestão de plugins. Clientes pertence à package opcional `pcteckserv/cms-customers`, fora do Core. Agenda e Imobiliário declaram dependência de Clientes no respetivo manifesto. Moloni, PayPal, Easypay e restantes módulos precisam de validação e versão compatível antes de migrar sites antigos.

## Instalação e desenvolvimento

Requer PHP compatível com Laravel 13 e as dependências do site. No workspace, o Starter usa um repositório Composer path com versão 3.0.0 e `symlink: false`. Atualizar com Composer após alterações da fonte; nunca corrigir diretamente `vendor`.

O provider regista migrations e os contratos administrativos. A migração `2026_11_07_000001_migrate_admin_two_factor` importa fatores administrativos confirmados antigos, preservando segredo, hashes de recuperação e proteção de repetição. Preservar APP_KEY. A migração de clientes e dos seus fatores pertence ao plugin Clientes 2; o Core não cria tabelas de clientes.

As classes antigas `CmsCore\Customers` e `CmsCore\Security\TwoFactor` foram substituídas. Não usar aliases para ocultar incompatibilidades nem instalar plugins Core 2 ignorando as restrições do Composer. Os dados antigos não são eliminados por esta troca de código.

## Validação

Definir `CMS_CORE_TEST_APP` para uma aplicação Laravel de teste com esta package e `CMS_COMPOSER_BINARY` para Composer. Executar `php <app>/vendor/phpunit/phpunit/phpunit -c phpunit.xml` e `node --test tests/Js/*.test.mjs`. Usar SQLite em memória nos testes, sem browser.

Ver `docs/migracao-novo-core3.md` para origem, impacto e procedimento. Versão **3.0.0**, distribuída pela tag `v3.0.0` do repositório principal. Instalações com plugins da linha Core 2 exigem migração antes de atualizar.

## Correções 3.0.1

Administração mobile: a navegação começa recolhida e os grupos internos não
abrem automaticamente pela página atual. O botão Menu de administração controla
a abertura; Escape fecha. A navegação desktop conserva o comportamento existente.

Media: um upload recusado pelo PHP apresenta uma mensagem legível sobre limites
e armazenamento temporário. A aplicação não consegue alterar limites do
alojamento. Ajustar upload_max_filesize/post_max_size no servidor; no ambiente
NextHome local foram confirmados 16M/256M e max_file_uploads=40. Servidores PHP
já iniciados precisam de reinício pelo respetivo operador.

## Correções 3.0.2

Uploads de 24 MB por ficheiro, máximo configurável de 240 MB por envio. Validação no servidor e aviso antecipado no administrador; os limites PHP e do proxy devem acompanhar estes valores.

## Correções 3.0.3

O botão «Ver site» usa o URL configurado em APP_URL, evitando endereços antigos guardados nas opções do CMS. Sem migrations.
