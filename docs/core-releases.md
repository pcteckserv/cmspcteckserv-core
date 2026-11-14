# Publicação de versões do Core

## v3.0.0

O endereço público passa a ser definido exclusivamente por `config('app.url')`,
normalmente através de `APP_URL`. As opções gerais e o URL base de SEO ficam
apenas de consulta; guardar estas opções deixa de escrever no `.env`.

O favicon, imagens SEO, canonicals e sitemap reconhecem referências internas
legadas e acompanham o endereço atual. Os URLs externos mantêm o domínio e os
valores antigos das opções são preservados para identificar referências legadas.
A cache de robots.txt distingue o domínio ativo.

**Atualização incompatível:** rever a restrição Composer do Core para `^3.0` e
atualizar em conjunto Loja 0.48.0 e Clientes 1.3.0 quando instalados.
Os registos históricos de aceitação conservam os URLs originais. Sem migrations
ou alterações aos dados dos sites. Consultar [Endereços do site](site-urls.md).

Validação: 32 testes específicos do Core, 143 da Loja e a suite de Clientes
(48 testes) passaram. As suites completas mantêm as falhas já reproduzidas na
origem: Core 202/209 aprovados, Loja 676/681 e Clientes 48/48. Sem falhas novas
nos testes alterados ou criados.


## v2.13.0

Acrescenta o catálogo partilhado de países e regiões e a gestão/importação nas
configurações do Core, com permissões próprias. A migration preserva o catálogo
anterior da Loja. Consulte [Países e regiões](geography.md) para atualização e API.

Validação: os quatro testes de geografia e os cinco testes JavaScript passaram.
A suite completa executou 195 testes: 189 passaram e mantiveram-se seis falhas
preexistentes de permissões, menus, gestão de plugins e SEO, reproduzidas no código
anterior. O manifesto Composer foi validado.

## v2.11.3

Remove os textos «Painel de administração» e «Navegação» da barra lateral,
mantendo o logótipo, os menus e as respetivas permissões. Remove também os
estilos que deixaram de ser utilizados. Sem migrations.

Validação: os seis testes da barra lateral e do registo de menus passaram, com
27 verificações. A suite completa executou 191 testes: 187 passaram e mantiveram-se
as quatro falhas preexistentes de permissões administrativas e SEO. O SCSS foi
compilado sem erros.

## v2.11.2

Melhora a barra lateral administrativa com logótipo fixo, deslocamento independente
do menu, submenus mais compactos e destaque da página atual em laranja suave.
Inclui foco visível por teclado, identificação acessível da página atual e respeito
pela preferência de movimento reduzido. Mantém as permissões e a integração dos
menus dos plugins, sem migrations nem alterações ao Starter ou aos plugins.

Validação: os seis testes da barra lateral e do registo de menus passaram, com
27 verificações. A suite completa executou 191 testes: 187 passaram e mantiveram-se
as quatro falhas preexistentes de permissões administrativas e SEO. O SCSS foi
compilado e o resultado foi verificado numa pré-visualização no navegador.

## v2.11.1

Corrige a descoberta de atualizações de plugins instalados como packages `path`
sem metadados de origem no registo do CMS. A origem é recuperada dos dados do
Composer; uma origem explícita continua a prevalecer. O catálogo tem de devolver
o mesmo package antes de disponibilizar ou aplicar uma atualização.

As atualizações alinham a restrição Composer com o manifesto do plugin quando
necessário, incluindo mudanças de versão minor em plugins `0.x`. O registo da
nova versão só é atualizado após confirmar os ficheiros instalados e terminar
as migrations e a limpeza de cache. O painel mostra «Versão por verificar» quando
não conhece a versão disponível, em vez de indicar «Atualizado».

Não introduz migrations nem altera conteúdo dos sites.

Validação: os 50 testes de instalação e atualização passaram, com 237 verificações.
A suite completa executou 187 testes: 183 passaram e mantiveram-se as quatro
falhas preexistentes de permissões administrativas e SEO, reproduzidas antes da
correção. O manifesto foi validado com `composer validate --strict --no-check-publish`.

## v2.11.0

Adiciona um cartão de autenticação de dois fatores em «Os meus dados», com
configuração por QR ou chave manual, confirmação por código TOTP e códigos de
recuperação de utilização única. O login só termina depois de validar o segundo
fator. Sessões anteriores e cookies de autenticação persistente também têm de
validar o 2FA para aceder ao `/admin`.

Inclui chaves encriptadas, hashes dos códigos de recuperação, proteção contra
reutilização de códigos, CSRF, limites por conta e IP e auditoria sem credenciais.
A configuração e a desativação exigem a palavra-passe atual. O QR é gerado
localmente com BaconQrCode; os códigos TOTP são validados com Google2FA.

Após atualizar as dependências Composer na aplicação, executar
`php artisan migrate --force`. É necessária a extensão PHP `xmlwriter`.
Consultar [a documentação de 2FA](two-factor-authentication.md) para configuração
e recuperação.

Validação: os 55 testes relacionados passaram, incluindo 21 testes novos de 2FA,
com 364 verificações. A suite completa executou 181 testes: 177 passaram e
mantiveram-se as quatro falhas preexistentes em permissões administrativas e SEO.
O manifesto foi validado com `composer validate --strict --no-check-publish` e a
auditoria das novas dependências não encontrou avisos de vulnerabilidades.

## v2.10.0

Substitui o botão de terminar sessão por um menu de perfil com acesso a
«Os meus dados». Cada utilizador autenticado pode alterar o nome, o email e a
palavra-passe da própria conta, sem necessitar de permissões de gestão de
utilizadores. Alterar o email ou a palavra-passe exige a palavra-passe atual.

Inclui validação do email único, confirmação da nova palavra-passe, proteção
CSRF, limitação de pedidos e auditoria sem credenciais. A alteração do email
remove a verificação anterior; a alteração da palavra-passe renova o token de
autenticação persistente e o identificador da sessão.

Validação: os 10 testes do perfil passaram, com 89 verificações. A suite completa
executou 160 testes: 156 passaram e 4 falharam. As mesmas quatro falhas foram
reproduzidas na versão anterior, nos testes de permissões administrativas e SEO.
O manifesto foi validado com `composer validate --strict --no-check-publish`.

## v2.7.0

Adiciona a eliminação definitiva de utilizadores, exclusiva do Super Admin, na
listagem e na edição. Mantém a desativação existente e a proteção das contas
Super Admin. A operação utiliza confirmação, autorização no backend, transação
e auditoria, com rollback em caso de falha ou de relações que impeçam a eliminação.

Validação: os 11 testes novos passaram, com 58 verificações. A suite completa
executou 128 testes: 118 passaram, com 4 falhas, 6 erros e 1 aviso de teste
arriscado. As mesmas falhas e erros foram reproduzidos na versão anterior,
nas áreas de acesso administrativo, SEO, consentimentos e atualizações de plugins.
O manifesto Composer foi validado com `composer validate --strict --no-check-publish`.

## v2.6.10

Atualização mínima de documentação para verificar a deteção e instalação de
novas versões através do painel de atualizações. Sem alterações funcionais ou
migrations.

## Processo de publicação

A versão Composer do Core é obtida pela tag Git (`vMAJOR.MINOR.PATCH`).
Não adicionar o campo `version` ao `composer.json`: uma versão diferente da
tag faz com que o Composer ignore essa release, embora o painel de atualizações
consiga detetar a tag.

Antes de publicar uma nova tag, validar o manifesto com
`composer validate --strict --no-check-publish` e executar os testes do Core,
incluindo `tests/Unit/CorePackageManifestTest.php`, através da aplicação de testes.

O `phpunit.xml` carrega o código-fonte local do Core através de `tests/bootstrap.php`.
Definir `CMS_CORE_TEST_APP` com o caminho de uma aplicação Laravel que tenha as
dependências de teste instaladas e executar o PHPUnit dessa aplicação com
`--configuration phpunit.xml`. Os testes usam SQLite em memória e isolam as
configurações de catálogo e atualizações do site utilizado como ambiente de teste.

Se uma tag já publicada contiver um manifesto inválido, publicar a correção numa
nova versão PATCH. Não mover nem substituir tags existentes. A tag `v2.6.7`
contém `version: 2.6.6`; a correção deve ser distribuída numa versão posterior.

Após a publicação, voltar a verificar as versões disponíveis no painel antes de
atualizar o site. Alterar apenas a branch principal não corrige o manifesto
guardado numa tag anterior.
## v3.1.2

Integra as melhorias já distribuídas no MaxiHome 3.0.3 com a linha central 3.1.1: dependências de plugins, compatibilidade declarada com o Core, perfis profissionais e avatares, preservação do 2FA legado, limites de media de 24 MB, menu móvel e versões incluídas no pacote do site. Mantém o tema global de email, a gestão de ícones, os URLs canónicos e a recuperação do resultado do Composer da linha central.

As migrations de perfis e 2FA são as que já acompanhavam a distribuição do MaxiHome; os registos e dados existentes são preservados. Os testes adicionais cobrem ativação/desativação de dependências e compatibilidade de metadados. Não existem tags de plugins nesta entrega; o Core continua a usar tags Git sem campo `version` no manifesto Composer.
