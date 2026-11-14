# Revisão isolada do módulo Atualizações

14/11/2026. Autorização específica para rever Atualizações, após uma entrega que não devia ter alargado o âmbito ao Core. Esta revisão não modifica autenticação, perfis, SEO, media, migrations, dependências ou contratos de outros módulos. Não é instalada em sites nem publicada uma nova tag do Core nesta entrega.

## Correções

- Só são consultadas atualizações dos plugins instalados e ativos. A lista de plugins apresenta apenas os que têm uma versão superior à instalada. O catálogo e os dados dos inativos permanecem intactos.
- O endpoint de atualização exclui plugins inativos; o executor também recusa plugins inativos e versões já instaladas, antes de executar Composer.
- O Starter mostra o nome do site e o identificador público do repositório definido em `STARTER_GITHUB_REPOSITORY`, sem expor credenciais. A versão configurada pelo site já tem precedência sobre o marcador histórico. Se a consulta do repositório atual falhar, uma versão disponível antiga não é reutilizada para o Starter.
- Um Core distribuído como `path` não pode ser atualizado pelo executor de tags remotas. Não se altera a versão declarada de um snapshot sem os ficheiros da origem correspondente. A recusa aplica-se também a pedidos diretos da mesma versão e acontece antes de modificar o manifesto ou executar Composer.
- As operações de atualização usam `--with-dependencies --minimal-changes`, mantendo os restantes requisitos de raiz bloqueados. Se uma atualização exigir alterar o Laravel ou outro requisito de raiz, o Composer deverá recusar a operação para validação separada.
- A configuração `cms-core.updates.allow_core_updates` permite ao próprio site proteger o Core. O valor por omissão é `true`, preservando a configuração dos restantes sites. Quando desativado, o servidor recusa a operação e o painel indica atualização manual.

## Validação

Suite completa do Core: **242 testes aprovados, 1238 asserções**. Inclui o endpoint HTTP, filtros, identidade do Starter, falha de consulta remota, recusas de snapshots, pedidos sem atualização e falhas de Composer sem execução de migrations.

Um teste executa Composer real com repositórios metapackage locais e rede desativada. Parte de um Core fictício 3.0.1, Laravel 13.36.0 e outro pacote 1.0.0. Disponibiliza versões 13.42.0 e 1.1.0, mais uma atualização fictícia do Core e a nova dependência necessária. A atualização troca apenas o Core de teste e instala essa dependência; Laravel e o outro pacote mantêm-se nas versões anteriores. As versões da fixture não constituem uma release.

```text
CMS_CORE_TEST_APP=<aplicação Laravel de ensaio>
CMS_COMPOSER_BINARY=<composer.phar>
php -d memory_limit=1024M <aplicação>/vendor/phpunit/phpunit/phpunit -c phpunit.xml --no-coverage
```

Usados PHP 8.4, Composer 2.11.2 e o MaxiHome como host isolado dos testes. Sem instalar ou editar o `vendor` do host. A recuperação FTP anterior continua separada desta revisão.

## Distribuição

A branch `fix/atualizacoes-seguras` contém esta revisão para comparação com a versão publicada. Não existe uma nova tag automática do Core nem atualização dos snapshots dos sites. O CGLimpezas 0.0.5 recebe extensões do próprio site que mantêm o seu Core 3.0.1 instalado.

Um atualizador antigo em produção não recebe estas proteções por simplesmente consultar o Git. Antes de uma futura migração do Core, instalar a proteção do próprio site, verificar a origem/versão efetivamente instaladas, resolver o lock em ambiente isolado e só então preparar a entrega correspondente. Esta revisão não promete rollback automático de qualquer instalação externa interrompida.

Ficheiros de execução alterados: `config/cms-core.php`, `src/Updates/PackageUpdater.php`, `src/Updates/PackageVersionRegistry.php`, `src/Http/Controllers/Admin/UpdatesController.php`, `resources/views/admin/updates/index.blade.php` e `resources/views/admin/updates/partials/packages-table.blade.php`. Os restantes ficheiros alterados são testes e esta documentação.
