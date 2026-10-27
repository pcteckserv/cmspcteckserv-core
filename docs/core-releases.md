# Publicação de versões do Core

## v2.20.0

A entrada e o registo de clientes podem retomar uma página pública através de
uma ligação temporária assinada. O retorno é validado contra a origem e a pasta
da instalação, preservado nas ligações entre entrada e registo e após dois
fatores. Destinos sem assinatura, externos ou expirados são recusados. A entrada
normal e o regresso ao checkout existente mantêm o seu comportamento.
Não exige migrações. A Agenda 0.17 usa este percurso para retomar a marcação.

## v2.19.1

A administração permite definir uma palavra-passe na criação do cliente e
substituí-la na ficha existente, com confirmação e pelo menos 12 caracteres.
A opção é explícita: sem ativação, ignora preenchimento automático e preserva
a senha atual. Sem senha inicial, mantém a recuperação de acesso existente.
Alterar a senha revoga o token de acesso persistente e as sessões antigas são
recusadas pelo controlo de identidade dos clientes. Exige core.customers.manage.
Não requer migrações nem alterações nos plugins.

## v2.19.0

O perfil profissional permite selecionar e adicionar idiomas, tanto no perfil
próprio como na edição administrativa. A nova coluna JSON languages é opcional;
a migração preserva os perfis existentes. Campos omitidos por integrações antigas
não alteram os idiomas guardados. Limite de 20 idiomas com 80 caracteres cada.

## v2.18.1

A atualização dos plugins do catálogo alinha também a restrição no composer.json
do projeto com version_constraint, em vez de manter restrições antigas como
^0.13.0 ou ^0.25.0. O Composer require resolve a versão e repõe os ficheiros de
manifesto e lock se a resolução falhar. Os metadados só avançam após confirmar
os ficheiros instalados, migrações e limpeza de cache. Teste com Composer real
para os sete plugins e repositórios locais antigos de prioridade inferior.

## v2.18.0

Permissões diretas passam a ter um modo explícito: quando ativo, ignoram as
permissões dos papéis. A migração preserva as atribuições diretas existentes.
Os formulários de edição exigem intenção explícita para alterar email ou senha,
com validação no servidor para proteger contra preenchimento automático.

A deteção de versões dos plugins consulta os metadados do catálogo, incluindo
instalações antigas sem origem registada. Uma versão não verificada deixa de
ser apresentada como atualizada. O catálogo é reutilizado durante o pedido.

## v2.17.0

Entrega avatares existentes em /storage/staff-avatars sem depender de storage:link,
incluindo instalações em subdiretórios. Só serve imagens locais públicas cujo
caminho esteja associado a um perfil. Valida nome, MIME e raiz real do ficheiro;
inclui cache condicional, nosniff e CSP restritiva.

O perfil apresenta a fotografia centrada, com ligação para abrir o original.
A edição administrativa de utilizadores inclui fotografia e apresentação,
reutilizando as validações do perfil. Editores sem permissão para gerir papéis
preservam as atribuições existentes. Não altera a proteção dos superadministradores.
Não requer novas migrações nem voltar a carregar as fotografias.

## v2.16.0

Remove a gestão global de menus e estilos, incluindo editores, componentes,
rotas públicas e injeção de CSS. A migração elimina apenas website.menus e
website.css da tabela partilhada cms_site_options; preserva políticas, registo,
rodapé e clientes. Os temas de cada site continuam responsáveis por navegação
e apresentação. Não usar esta versão com temas que chamem o antigo x-cms-menu.

Mantém os clientes independentes do Ecommerce e os nomes físicos históricos
para preservar contas e referências. A atribuição inicial de papéis de cliente
usa lotes de 500 registos, sem alterar palavras-passe ou moradas.

As páginas legais do Core passam a rotas de recurso: páginas do tema têm
prioridade, também com route cache. Os middlewares de consentimento e atalho
administrativo preservam a vista original ao modificar o HTML da resposta.
Consulte [Compatibilidade das atualizações](site-upgrades.md).

## v2.15.0

Reorganiza o editor de menus com catálogo de ligações extensível pelos plugins,
pesquisa, adição em massa, arrasto e botões de movimento. Permite duplicar ramos,
criar submenus, remover elementos preservando os filhos e anular operações.
Inclui pré-visualização da estrutura, título, descrição, classes CSS, relação da
ligação e endereços de email/telefone. A atribuição de cabeçalho ou rodapé liberta
a localização anterior sem apagar o menu. O layout adapta-se a telemóveis.

## v2.14.5

Republica o Core para permitir uma nova atualização das instalações que
indicaram 2.14.4 sem disponibilizar todos os ficheiros. Inclui
`src/Http/Controllers/PublicMediaController.php` e a respetiva rota em
`routes/web.php`, já presentes no código de 2.14.4. A nova tag permite
ao Composer voltar a instalar uma release posterior, sem alterar a tag anterior.

Após atualizar no painel, confirmar a versão instalada e a presença do
controlador em `vendor/pcteckserv/cms-core/src/Http/Controllers/`.
Os URLs existentes de media mantêm-se; não alterar o disco público nem
mover os ficheiros para repetir esta atualização.

## v2.14.4

Adiciona entrega pública controlada dos ficheiros da biblioteca no endereço
`/storage/cms/media/...` (ou no diretório de media configurado). Os URLs
existentes mantêm-se válidos quando o alojamento não tem `storage:link`,
incluindo instalações antigas em que a aplicação ficou na pasta pública.
Não move os ficheiros nem altera os registos ou URLs gravados.

A entrega só permite caminhos registados no Media correspondente ao UUID,
em discos locais com visibilidade pública, incluindo variantes conhecidas.
Ficheiros privados, apagados, inexistentes e caminhos fora da raiz do disco
são rejeitados. Respostas usam cache condicional, MIME detetado pelo servidor,
`nosniff` e CSP restritiva. Com symlink funcional, o servidor continua a servir
diretamente os ficheiros. Novas instalações mantêm a separação das pastas.

Inclui a reparação do atualizador de 2.14.3. Uma instalação ainda em 2.14.1
precisa primeiro dos dois ficheiros de reparação por FTP, na pasta onde estão
`artisan` e `composer.json`, preservando a estrutura `vendor/...`. Depois,
atualizar o Core no painel: o Composer descarrega esta versão do repositório
e `optimize:clear` disponibiliza a nova rota. Não reinstalar a base de dados.

Validação: 160 testes do Core e 680 asserções aprovados, incluindo a atualização
real com Composer/Git e a entrega pública sem symlink e com URL em subpasta.

Validação: 160 testes do Core e 680 asserções aprovados, incluindo a atualização
real com Composer/Git e a entrega pública sem symlink e com URL em subpasta.

## v2.14.3

Corrige a atualização do Core em instalações publicadas a partir de um pacote
com repositories Composer `path`. A cópia em `packages/pcteckserv/cms-core`
serve para a instalação inicial; a atualização passa a dar prioridade ao
repositório VCS definido em `CMS_CORE_REPOSITORY_URL`, limitado ao Core.
As origens locais deixam de fornecer esse package e a recuperação automática
não volta a introduzir a versão antiga do lock. Os plugins locais mantêm as
suas origens e versões. A versão e os ficheiros instalados são confirmados.

Corrige também o fallback SEO: o título da página prevalece sobre o título
global quando não existe título SEO explícito. Mantém a prioridade de overrides
e metadados SEO. Atualiza os testes de permissões para verificar o DOM e os
rótulos atuais, e isola os testes de plugins/Composer da configuração do site.

O teste de instalação publicada usa Composer e um repositório Git local real,
com duas tags e conteúdos diferentes, confirmando a atualização do código e
a preservação de um plugin local. Não executa migrations numa base real.

Para desbloquear instalações com o atualizador anterior, substituir primeiro
`vendor/pcteckserv/cms-core/src/Updates/PackageUpdater.php` e
`vendor/pcteckserv/cms-core/src/Support/ComposerRepositoryCleaner.php` pelas
versões desta release. Depois executar a atualização normal em `/admin/updates`.
Esta reparação não altera o número de versão instalado: a confirmação da nova
versão só acontece depois do Composer instalar os ficheiros do repositório.

Validação final: suite completa do Core com 156 testes e 664 asserções,
sem falhas ou erros; site anfitrião com 45 testes e 186 asserções aprovados.

## v2.14.2

A página `/admin/updates` identifica o projeto pelo `APP_NAME` (`app.name`)
e apresenta o repositório GitHub definido em `STARTER_GITHUB_REPOSITORY`.
O nome Starter deixa de aparecer no título, na identificação do projeto e
nas mensagens da atualização. Core e plugins continuam separados.
O identificador interno mantém compatibilidade com o registo de versões,
rotas, permissões e histórico. O endereço apresentado nunca inclui credenciais.

Validação: 21 testes de atualizações aprovados (89 asserções). Suite completa
do Core: 154 testes, 148 aprovados, com as seis falhas anteriores nas áreas
de permissões, SEO e configuração dos testes de plugins/Composer.
Os 45 testes do site anfitrião também passaram (186 asserções).

## v2.11.0

Adiciona QR code local à configuração TOTP, mantendo a chave manual como
alternativa. O QR só é apresentado enquanto a ativação está pendente e a página
usa cache privada desativada. Adiciona o menu pessoal no canto superior direito,
com perfil, segurança e saída, e edição do próprio perfil protegida pela
palavra-passe atual. A área de cliente ganha navegação própria e atalhos para o
histórico dos plugins ativos. Requer instalação das dependências Composer e
reconstrução dos assets; sem novas migrations.

Testes de browser descodificam os QR codes de utilizador/cliente e verificam a
disposição em telemóvel, a navegação dos menus e o formulário de perfil. A suite
isolada cobre ainda palavra-passe atual, email duplicado, rejeição de alteração
de outros utilizadores/roles e sessões revogadas.

Validação final: 203 testes/1466 verificações na regressão funcional, 39 testes
de browser (incluindo leitura dos QR codes) e 42 testes/146 verificações da
aplicação passaram. A suite habitual do Core mantém 143/150 testes a passar,
com os mesmos quatro testes em falha e três erros já documentados em 2.10.0.
Build Vite e manifesto Composer validados.

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
