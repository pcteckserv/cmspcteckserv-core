# Core 3.1.4 — módulo Atualizações

14/11/2026. Autorização específica para rever Atualizações, após uma entrega que não devia ter alargado o âmbito ao Core. A revisão foi inicialmente isolada numa branch e a sua publicação como 3.1.3 foi posteriormente solicitada. Esta correção, relativamente a 3.1.2, não modifica autenticação, perfis, SEO, media, migrations, dependências ou contratos de outros módulos. Não é instalada automaticamente nos sites.

A 3.1.4 corrige a apresentação que escondia plugins ativos já atualizados. Mantém as proteções da 3.1.3 e altera apenas os templates do módulo, testes e documentação.

## Correções

- Só são consultadas atualizações dos plugins instalados e ativos. O inventário apresenta todos esses plugins, incluindo os que não têm atualização; apenas as versões novas têm a ação Atualizar. O catálogo e os dados dos inativos permanecem intactos.
- O botão Verificar atualizações repete as consultas por GET sem iniciar instalações. Cada linha apresenta a última tentativa e o estado; versões desconhecidas ficam por verificar. Uma verificação desativada é identificada como tal.
- O endpoint de atualização exclui plugins inativos; o executor também recusa plugins inativos e versões já instaladas, antes de executar Composer.
- O Starter mostra o nome do site e o identificador público do repositório definido em `STARTER_GITHUB_REPOSITORY`, sem expor credenciais. A versão configurada pelo site já tem precedência sobre o marcador histórico. Se a consulta do repositório atual falhar, uma versão disponível antiga não é reutilizada para o Starter.
- Um Core distribuído como `path` não pode ser atualizado pelo executor de tags remotas. Não se altera a versão declarada de um snapshot sem os ficheiros da origem correspondente. A recusa aplica-se também a pedidos diretos da mesma versão e acontece antes de modificar o manifesto ou executar Composer.
- As operações de atualização usam `--with-dependencies --minimal-changes`, mantendo os restantes requisitos de raiz bloqueados. Se uma atualização exigir alterar o Laravel ou outro requisito de raiz, o Composer deverá recusar a operação para validação separada.
- A configuração `cms-core.updates.allow_core_updates` permite ao próprio site proteger o Core. O valor por omissão é `true`, preservando a configuração dos restantes sites. Quando desativado, o servidor recusa a operação e o painel indica atualização manual.

## Validação

Suite completa do Core: **243 testes aprovados, 1247 asserções**. Inclui o endpoint HTTP, inventário e ações elegíveis, identidade do Starter, falha de consulta remota, recusas de snapshots, pedidos sem atualização e falhas de Composer sem execução de migrations.

Os templates foram também exercitados em Chrome a 1440, 390 e 320 px, com DTOs/rotas de uma aplicação isolada. Foram confirmados o inventário, versões desconhecidas, nova verificação por GET sem instalação e ausência de overflow. Estes ensaios de apresentação não instalam o novo Core nos sites.

Um teste executa Composer real com repositórios metapackage locais e rede desativada. Parte de um Core fictício 3.0.1, Laravel 13.36.0 e outro pacote 1.0.0. Disponibiliza versões 13.42.0 e 1.1.0, mais uma atualização fictícia do Core e a nova dependência necessária. A atualização troca apenas o Core de teste e instala essa dependência; Laravel e o outro pacote mantêm-se nas versões anteriores. As versões da fixture não constituem uma release.

```text
CMS_CORE_TEST_APP=<aplicação Laravel de ensaio>
CMS_COMPOSER_BINARY=<composer.phar>
php -d memory_limit=1024M <aplicação>/vendor/phpunit/phpunit/phpunit -c phpunit.xml --no-coverage
```

Usados PHP 8.4, Composer 2.11.2 e o MaxiHome como host isolado dos testes. Sem instalar ou editar o `vendor` do host. A recuperação FTP anterior continua separada desta revisão.

## Distribuição

A tag `v3.1.4` publica a correção do inventário, mantendo as proteções da `v3.1.3`. As tags anteriores mantêm-se intactas no histórico; não foram movidas, substituídas ou reutilizadas. Não foram atualizadas as dependências instaladas nem os snapshots dos sites. O CGLimpezas 0.0.6 recebe a apresentação corrigida no próprio site e mantém o seu Core 3.0.1 instalado.

A publicação da tag não corrige retroativamente um registo Composer incorreto nem reinstala ficheiros num servidor. No MaxiHome diagnosticado, os ficheiros pertenciam ao snapshot 3.0.3 e o registo 3.1.2 era incorreto. Não instalar esta tag sobre esse estado como substituto de uma recuperação verificada.

Um atualizador antigo em produção não recebe estas proteções por simplesmente consultar o Git. Antes de uma futura migração do Core, instalar a proteção do próprio site, verificar a origem/versão efetivamente instaladas, resolver o lock em ambiente isolado e só então preparar a entrega correspondente. Esta revisão não promete rollback automático de qualquer instalação externa interrompida.

Ficheiros de execução alterados: `config/cms-core.php`, `src/Updates/PackageUpdater.php`, `src/Updates/PackageVersionRegistry.php`, `src/Http/Controllers/Admin/UpdatesController.php`, `resources/views/admin/updates/index.blade.php` e `resources/views/admin/updates/partials/packages-table.blade.php`. Os restantes ficheiros alterados são testes e esta documentação.
