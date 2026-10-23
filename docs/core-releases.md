# Publicação de versões do Core

## v2.10.0

Centraliza clientes, grupos e moradas no Core, com área privada independente dos
plugins e histórico extensível de encomendas e marcações. O menu Utilizadores
reúne Utilizador, Cliente, Segurança da conta e Regras de acesso. Introduz as
roles Cliente (`core.client`) e Gestor de loja (`ecommerce.manager`), mantendo
as sessões de cliente separadas do acesso administrativo.

Adiciona 2FA opcional com aplicação autenticadora, confirmação antes de ativar,
códigos de recuperação de uso único, proteção contra reutilização de códigos e
desafios limitados e com expiração. Migrações preservam IDs, credenciais e
relações existentes. Ver [clientes e segurança](customers.md) para atualização,
permissões, compatibilidade e extensão por plugins.

Validação: 199 testes/1429 verificações na suite isolada de clientes/Core e
regressão de Loja/Moloni/Agenda, 36 testes de browser em desktop/telemóvel e
42 testes/146 verificações da aplicação anfitriã passaram. Build Vite e
manifestos Composer validados.
A suite habitual do Core executou 150 testes: 143 passaram, quatro falharam e
três terminaram em erro, com um teste arriscado. Os mesmos sete testes em falha
foram reproduzidos no Core anterior em cópia isolada: formulários de permissões,
fallback SEO e mocks/configuração do sistema de atualizações/Composer. A alteração
do submenu tem cobertura atualizada e não acrescenta testes em falha à comparação.

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
