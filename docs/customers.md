# Clientes centrais e segurança de contas — Core 2.10

## Navegação e permissões

O menu **Utilizadores** reúne Utilizador, Cliente, Segurança da conta e Regras de
acesso. Cada entrada administrativa é limitada pela respetiva permissão.

- `core.customers.view`: consultar a listagem e as fichas.
- `core.customers.manage`: criar/editar/inativar clientes, gerir moradas e grupos.
- `core.client.access`: acesso à própria área de cliente, sem administração.
- Role `core.client` (Cliente): atribuída às contas de cliente; nunca concede
  permissões administrativas, mesmo que sejam atribuídas permissões à role.
- Role `ecommerce.manager` (Gestor de loja): catálogo, encomendas, expedição,
  transportadoras, cupões, relatórios e clientes. Não inclui gestão de utilizadores,
  regras de acesso, integrações, credenciais ou confirmação manual de pagamentos.

Os grupos de clientes são segmentação comercial, não roles nem permissões.

## Identidades e compatibilidade

`Pcteckserv\CmsCore\Customers\Customer` é a identidade partilhada pelos plugins.
Os funcionários continuam a ser utilizadores existentes no CRM. As sessões de
cliente (`shop_customer`, nome preservado por compatibilidade) são separadas da
sessão administrativa (`web`). Não existe unificação automática de credenciais
de funcionário/cliente por coincidência de email.

O Core passa a ser proprietário dos clientes, moradas e recuperação de acesso.
Os nomes físicos `cms_shop_customers`, `cms_shop_addresses` e
`cms_shop_password_resets` são preservados para manter IDs, palavras-passe e
chaves estrangeiras existentes. Não são uma dependência funcional da Loja.
O Core funciona sem qualquer plugin instalado; a Loja também pode ser instalada
posteriormente sem recriar estas tabelas. Não remover estas tabelas ao desinstalar
a Loja. As classes da Loja permanecem como adaptadores de compatibilidade.

A área pública é `/cliente`: entrada, registo, recuperação, perfil e moradas.
As rotas antigas `/loja/cliente/*` continuam disponíveis. O histórico da conta
inclui apenas registos associados e autorizados, nunca encomendas de terceiros
obtidas apenas pela coincidência de email.

Novos contactos criados pela Agenda recebem uma palavra-passe aleatória que não
é apresentada. Isso não inicia sessão nem altera um perfil existente. O titular
pode definir a palavra-passe através da recuperação enviada para o seu email.
Marcações de convidados só aparecem na área privada após associação com sessão
de cliente e ligação assinada da marcação. A administração conserva o histórico
destes contactos sem o tornar público.

## Extensão por plugins

Registar no `boot()` do provider uma contribuição em `CustomerModules::register`:
slug do plugin, título, permissão de consulta, callback `hasHistory(Customer)` e
callback `records(Customer, bool $admin)`. Os registos devem fornecer `title`,
`date`, `status`, `amount` e `url`; podem ser um paginator Laravel com `through()`.

As secções só são apresentadas com o plugin ativo e a permissão necessária.
A proteção contra eliminação consulta o histórico mesmo com o plugin inativo.
Cada plugin deve ainda usar FK com `restrictOnDelete` para preservar movimentos.
O callback deve limitar os registos ao cliente e, para funcionários, ao âmbito
autorizado. A Agenda respeita o acesso às agendas individuais.

Alterar uma morada guardada não altera os snapshots de encomendas antigas.
Clientes com histórico são inativados; clientes sem histórico podem ser removidos.
As antigas permissões de gestão de clientes da Loja são migradas para o Core.

## Autenticação em dois passos

Segurança da conta permite ativar TOTP (aplicação autenticadora), confirmar o
primeiro código e receber oito códigos de recuperação de uso único. A configuração
é manual pela chave da aplicação; não envia segredos para serviços de QR externos.
Exige palavra-passe para configurar e palavra-passe+código para desativar.

O segredo é cifrado com `APP_KEY`; os códigos de recuperação são guardados como
hashes dentro de um campo cifrado. Preservar e proteger a chave da aplicação.
Os códigos TOTP usam SHA-1, seis dígitos, passos de 30 segundos e tolerância de
um passo. O último passo aceite é persistido com bloqueio transacional para
impedir reutilização. Os desafios expiram em cinco minutos e têm throttling.
Alterar a palavra-passe invalida desafios pendentes e validações anteriores de
2FA. Recuperar a palavra-passe não desativa o segundo fator.

2FA é opcional para clientes e funcionários. Não existe ainda política global
que obrigue todos os administradores a ativá-lo. O acesso só é concluído após o
segundo fator; sessões restauradas também têm de o validar.

Configuração em `cms-core.customers`: `registration`, `terms_url`, `privacy_url`.
Personalizar as ligações para os documentos reais do site.

## Atualização e testes

Atualizar Core para `^2.10`, Loja para 0.20.0 e Agenda para 0.2.0. Executar
`php artisan migrate --force` e `php artisan cms:permissions-sync`, reconstruir os
assets administrativos e limpar caches de rotas/views na instalação de destino.
As migrations são incrementais e preservam os dados existentes. Os rollbacks do
Core preservam contactos, credenciais e roles atribuídas.

Os testes `tests/Standalone/SharedCustomersTest.php` usam Orchestra Testbench 11
com SQLite e são executados separadamente, sem depender de um site ou da Loja.
A suite habitual do Core continua a usar o `Tests\TestCase` do site anfitrião.
Os testes da Loja incluem instalação após o Core; os da Agenda cobrem identidade
partilhada, isolamento, associação assinada e proteção do histórico com plugin
inativo. Não são efetuados pagamentos reais nem enviados emails reais nos testes.
