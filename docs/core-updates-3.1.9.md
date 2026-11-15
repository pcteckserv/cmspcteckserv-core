# Verificação do atualizador — Core 3.1.9 e manutenção 2.9.2

O CGLimpezas conservou Core 3.0.1 porque a preparação da atualização falhou.
O erro confirmado foi uma instalação Composer com `installation-source=source`
cuja pasta `.git` não foi enviada no deploy por FTP. A correção anterior dos
plugins em `packages` não resolvia este segundo caso.

## Alterações limitadas ao atualizador

- Depois de validar o plano, os pacotes alterados são reinstalados na cópia
  privada. Apenas nessa cópia são retirados os ficheiros e os registos antigos
  desses pacotes. O Composer deixa de tentar atualizar um checkout Git incompleto.
- As versões e identidades dos outros pacotes continuam congeladas. Não se usa
  `--with-all-dependencies` para contornar conflitos com Laravel ou plugins.
- No Windows, a pasta de trabalho Composer pode impedir a troca da pasta.
  Prepara-se outra cópia dos ficheiros de execução, sem `.git`, e verifica-se
  novamente o Core e o autoload antes da troca. Exige espaço
  adicional em disco. Linux conserva o checkout preparado.
- Os downloads temporários `vendor/composer/tmp-<hash>` não são transportados
  para a preparação nem publicados como dependências. Permanecem na cópia
  anterior de vendor quando essa cópia é preservada.
- Uma nova versão de um plugin path que exige outro Core é recusada antes dos
  comandos Composer. A mensagem identifica a restrição e pede o Core compatível.
  Formulários 2 exige Core `^3.1.0`; Core 3.0.1 deve ser atualizado primeiro.
- A seleção de tags aceita versões estáveis da mesma versão principal. Core 2
  conserva também a mesma linha minor: StudioFranco 2.9 permanece em 2.9.
- Uma candidata Core incompatível em cache é limpa e a versão Core incluída no
  Starter não substitui uma candidata remota válida. O endereço histórico conhecido
  do repositório Core é mapeado para o oficial; repositórios personalizados mantêm-se.
- As mensagens Composer preservam o fim do erro, retiram caracteres de terminal
  e ocultam credenciais. O aviso de Composer antigo não é tratado como a causa.
- O caminho completo de um Composer executável é aceite diretamente. Antes era
  procurado como nome dentro de PATH e falhava, por exemplo, com
  `/usr/local/bin/composer`. Mantêm-se as verificações de leitura e execução.
- O CI anterior falhava antes dos testes: o hash de `resources/icons/build-catalog.py`
  variava com CRLF no Windows e LF no Linux. Python passa a ter hash canónico LF
  e atributo Git `eol=lf`. Os manifestos históricos 3.1.7/3.1.8 e 2.9.1 podem ser
  validados com a mesma sequência de texto em CRLF; alterações de conteúdo continuam
  recusadas. A tag nova é verificada também num arquivo Git com autocrlf desativado.

Não há alterações de funcionalidades, conteúdos, `.env`, bases de dados ou
dependências dos sites originais. As regras de manutenção estão em `AGENTS.md`.
Tags anteriores não devem ser movidas nem reescritas.

## Passagem do CGLimpezas por FTP

Uma tag nova não corrige o atualizador antigo que já está instalado. Para Core
3.0.1 com o carregador `SiteComposerTransaction` existente, exportar a ponte:

```text
php tools/build-updater-bridge.php /destino/ponte.zip https://cglimpezas.pt
```

O ZIP contém os quatro ficheiros genéricos da distribuição para
`app/bootstrap/updates`: transação, snapshot, política de versões e integridade.
Guardar a cópia anterior, enviar os helpers e a transação por último, enviar o
finalizador para `public_html` e concluir a validação pelo endereço privado no ZIP.
O finalizador recusa outra versão de Core, ficheiros incompletos ou diferentes e
um carregador inesperado. Não executa Composer; apenas invalida a cache dos quatro
ficheiros e elimina-se após sucesso. Expira em 48 horas.

Só depois verificar as atualizações e atualizar o Core pelo painel. Atualizar os
plugins após o Core compatível. Não aplicar esta ponte ao StudioFranco 2.9 nem
substituir manualmente o `composer.json`, lock, vendor ou a versão declarada.

## Outras instalações anteriores

Core 3.1.1 ainda tem o ramo antigo de atualização. O exportador
`tools/build-core31-ftp-bootstrap.php` gera a passagem para essa versão: quatro
helpers genéricos, quatro payloads e um finalizador. Os ficheiros existentes só
são alterados após validar os ficheiros base, a versão, o payload e Laravel. O ramo
Core passa a delegar na transação genérica; o registo e a seleção de atualizações
usam as classes comuns da versão nova para não conservar a candidata do Starter.
Os outros fluxos do PackageUpdater não são reescritos.
Existe cópia anterior, verificação de ativação da proteção e eliminação dos temporários.

Core 3.1.7/3.1.8 pode ficar bloqueado pelo hash Python incorreto do seu próprio
manifesto. `tools/build-integrity-ftp-repair.php` gera um único PHP para public_html.
Confirma o manifesto e o verificador da tag original, o Python LF byte a byte e
todos os restantes ficheiros. Só corrige o hash conhecido desse Python no manifesto,
com cópia anterior e validação final. Não altera código, versão, Composer, ambiente
ou dados, nem executa comandos. Se houver outra diferença, recusa a reparação.

Estas passagens exigem um checkout com as tags de origem para serem exportadas.
O utilizador do alojamento só necessita do FTP e do endereço privado incluído no ZIP.

## Passagem do StudioFranco

A linha `maintenance/2.9` recebe a mesma transação genérica e a seleção de versões
2.9. Não há uma migração automática para Core 3. A passagem FTP de Core 2.9.0 é
exportada por `tools/build-legacy-updater-hotfix.php` nessa linha, em grupos ordenados.
O finalizador verifica também os ficheiros base do Core e a coincidência entre
ficheiros e metadados Laravel, pois uma tentativa antiga com `-W` pode ter deixado
uma instalação parcialmente alterada.

## Critérios de validação e limites

Testes com Composer real cobrem ZIP, Git sem `.git`, uma segunda atualização,
plugins em `packages` com localização vendor antiga, remoção de dependências,
conflitos, versões principais incompatíveis, integridade e reposição de ficheiros.
Os exportadores FTP são testados com HTTP local e com autoload autoritativo antigo.

As suites do Core são executadas em PHP 8.3, 8.4 e 8.5; a verificação com MariaDB
usa uma instância descartável em loopback. As verificações dos quatro sites usam
cópias e dados sintéticos. Uma sobreposição de código para testar rotas não equivale
a uma atualização Composer da instalação online. Não há testes de pagamentos reais,
envio real de email ou validação visual completa em Chrome.

Existem falhas de referência na suite histórica 2.9 executada no host StudioFranco:
três expectativas antigas em `AdminAccessRulesTest`, a versão de plugin existente
no host em `InstalledPluginUpdatesTest` e um fallback em `SeoModuleTest`. Devem ser
comparadas com a origem, documentadas e não anunciadas como uma suite aprovada.
Uma falha nova ou uma verificação obrigatória por concluir bloqueia a publicação.

Uma reposição de ficheiros não reverte migrações já executadas na base de dados.
Os backups continuam necessários. O PHP do alojamento necessita das extensões,
permissões, espaço e acesso autenticado ao repositório aplicáveis à instalação.
Os testes locais não certificam todos os alojamentos nem dependências futuras.

## Evidência local — 15 de novembro de 2026

- Core 3: suite completa de 292 testes, 1455 asserções por execução, aprovada nas
  seis combinações PHP 8.3/8.4/8.5 e SQLite/MariaDB, em hosts e schemas independentes.
  Os testes de integridade também passaram nos três PHP (8 testes, 17 asserções).
- O primeiro CI Linux expôs a resolução incorreta do caminho Composer completo.
  A falha foi reproduzida antes da correção com um teste adicional. Os 28 testes
  afetados de comandos e instalação de plugins passaram nos três PHP locais;
  o novo CI tem 293 testes por combinação e é confirmado antes da tag.
- MySQL 8.4 revelou cinco expectativas de testes que dependiam da ordem das
  chaves ou dos espaços de colunas JSON. As comparações passaram a preservar
  tipos e ordem de listas, ignorando apenas a ordem das chaves de objetos, ou a
  comparar os valores efetivamente persistidos antes/depois da migração.
  Não foram alteradas migrações nem dados para contornar estas diferenças.
- Atualização integral de uma cópia da tag real 3.0.1, sem `.git`, para o arquivo
  candidato 3.1.9: Laravel v13.42.0 e as outras 107 versões instaladas preservadas,
  migrações e descoberta reais, dados sintéticos e ambiente preservados, backup do
  vendor e novo arranque Laravel com `view` e `translator` confirmados.
- Core 2.9: 182 testes, 177 aprovados, 768 asserções; apenas as cinco falhas de
  referência identificadas acima. Essas cinco foram reproduzidas a partir da tag
  2.9.1 no mesmo host. Os testes novos do atualizador e da integridade passaram.
- Arquivos Git com autocrlf desativado validados para ambas as distribuições.
  O verificador da tag antiga 3.1.8 também aceitou o arquivo canónico 3.1.9.
- Exportações FTP testadas por HTTP: autenticação, transferências incompletas e
  adulteradas, versão errada, Laravel parcialmente alterado, backup, ativação de
  código com autoload autoritativo antigo e eliminação dos temporários.
- Correção do manifesto testada nas tags reais 3.1.7 e 3.1.8 em formato Linux:
  só o hash Python conhecido foi corrigido; código, ambiente, Composer e versões
  permaneceram iguais. O verificador antigo passou a aceitar a distribuição.
- Quatro verificações de rotas Core/plugins (40 asserções), 149 pedidos HTTP,
  autenticação/CSRF e 49 assets aprovados; 3020 ficheiros controlados e HEAD/status
  dos quatro sites originais confirmados sem alterações. JavaScript: 8 testes.

Suites próprias dos sites, em cópias com assets compilados:

| Site | Total | Aprovados | Falhas/erros remanescentes | Ignorados |
| --- | ---: | ---: | ---: | ---: |
| CGLimpezas | 141 | 129 | 0 | 12 |
| MaxiHome | 501 | 500 | 1 | 0 |
| StudioFranco | 98 | 98 | 0 | 0 |
| Turkesa | 550 | 532 | 7 | 11 |

Não surgiram regressões atribuíveis a este patch. A falha MaxiHome é uma
expectativa antiga pela versão 3.1.2 no catálogo e já existia na referência.
Quatro problemas Turkesa já existiam na referência inicial; três mocks de
`ConfiguredPluginRepositoryTest` que não admitem `ensureDirectoryExists` foram
também reproduzidos com Core 3.1.8. A reconstrução local histórica do Shop Turkesa
tem limitações próprias. Estas suites não são apresentadas como totalmente aprovadas.
Os relatórios locais e os resultados do CI devem ser consultados antes da tag.
