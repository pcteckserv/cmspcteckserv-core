# Publicação de versões do Core

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

Se uma tag já publicada contiver um manifesto inválido, publicar a correção numa
nova versão PATCH. Não mover nem substituir tags existentes. A tag `v2.6.7`
contém `version: 2.6.6`; a correção deve ser distribuída numa versão posterior.

Após a publicação, voltar a verificar as versões disponíveis no painel antes de
atualizar o site. Alterar apenas a branch principal não corrige o manifesto
guardado numa tag anterior.
