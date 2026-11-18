# Validação do Core 3.5.1 — 18/11/2026

## Implementação e segurança

O diagnóstico do MaxiHome confirmou duas ligações Composer internas; a preparação confirma nome e âmbito e publica cópias independentes. Ligações externas, ciclos e identidades diferentes são recusados. A CG confirmou Agenda ausente do Composer mas presente no inventário administrativo. A reconciliação exige concordância dos inventários e conserva dados e ficheiros. Pacotes instalados mantêm a remoção transacional e a proteção de dependentes.

A correção reside no Core; os Starters reutilizam a transação e a CG delega no instalador central quando disponível. Não foram criadas tabelas nem alteradas credenciais. Os ZIP e os respetivos acessos privados ficam fora do Git.

## Ficheiros alterados

- `core-release.json`
- `docs/atualizacoes-3.5.1.md`
- `src/Plugins/PluginInstaller.php`
- `src/Plugins/PluginManager.php`
- `src/Updates/ComposerDeploymentTransaction.php`
- `src/Updates/ComposerInstalledPackageReader.php`
- `tests/Feature/CompatibleCoreUpdaterTest.php`
- `tests/Feature/DeployedPluginRemovalTest.php`
- `tests/Feature/PathPluginUpdateTest.php`
- `tests/Feature/StalePluginInventoryTest.php`
- `tests/Feature/VendorDirectoryLinksTest.php`
- `tools/build-snapshot-core-bootstrap.php`

## Validação

- PHP 8.3 / sqlite: 348 testes, 1792 asserções.
- PHP 8.4 / sqlite: 348 testes, 1792 asserções.
- PHP 8.5 / sqlite: 348 testes, 1792 asserções.
- PHP 8.3 / mysql: 348 testes, 1792 asserções.
- PHP 8.4 / mysql: 348 testes, 1792 asserções.
- PHP 8.5 / mysql: 348 testes, 1792 asserções.
- JavaScript: 8 testes aprovados.
- maxihome: 553 aprovados, 0 ignorados, 5859 asserções.
- cglimpezas-base: 131 aprovados, 12 ignorados, 738 asserções.
- cglimpezas: 131 aprovados, 12 ignorados, 740 asserções.
- starter: 131 aprovados, 11 ignorados, 858 asserções.
- Browser: quatro aplicações, desktop 1440 e mobile 390; página pública, login, CSRF, autenticação, atualizações, plugins, assets e fluxos administrativos. 14 verificações, sem erros JavaScript.
- FTP: duas aplicações; chave, expiração, integridade, destino, pré-visualização sem escrita, rollback, atualização real, dependências/dados, modo no-dev/autoload autoritativo e limpeza do finalizador. 12 verificações por aplicação.

Os comandos dos wrappers executam PHPUnit real com `--no-coverage --fail-on-warning --fail-on-notice --fail-on-deprecation`, Composer real e as bases MariaDB descartáveis `cms_core_test_351_*` em loopback. Os hosts Core foram novos, preparados com `php tools/prepare-test-host.php <host> 3.5.1`.

```powershell
node output/run-core351-matrix.cjs
node output/test-sites351-resume.cjs
node output/retest-sites351.cjs
$taskRoot=(Get-Content output/max351-temp-root.json -Raw | ConvertFrom-Json).root
node output/test-core351-bootstrap.cjs $taskRoot
$taskRoot=(Get-Content output/cg351-ftp-root.json -Raw | ConvertFrom-Json).root
node output/test-core351-bootstrap-cg.cjs $taskRoot
node output/start-admin351-browser.cjs
node output/check-sources351.cjs
```

## Limites

Validação em cópias privadas; não equivale a uma instalação já concluída no alojamento. Google requer os retornos autorizados e consentimento real; cron do alojamento continua a ser externo. Laravel e plugins dos sites existentes foram preservados. A CG Limpezas original mantém a base 3.0.1. A base genérica conserva Laravel 13.43/Symfony 7.4 e a plataforma PHP 8.3 previamente corrigidos.

As falhas iniciais foram expectativas antigas de testes: um mock sem `isAbsent`, e registos sintéticos apresentados como instalados sem um inventário Composer correspondente. Corrigidas as simulações, as suites acima foram repetidas. Um ensaio privado interrompido foi retomado e a falha controlada do artisan foi reposta antes de concluir o teste; nenhuma instalação original foi usada.

Um ensaio FTP paralelo atingiu o timeout de 300 segundos do Composer, preservando a instalação anterior. O percurso MaxiHome foi repetido numa cópia independente no volume temporário, com o mesmo código e timeout, e concluiu as 12 verificações. Os percursos Windows do ensaio foram expandidos e os valores dotenv com espaços foram colocados entre aspas.
