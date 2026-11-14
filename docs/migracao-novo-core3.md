# Migração para a base NOVO — Core 3

Atualização local de 7 de novembro de 2026. Origem: `NOVO/pcteckcms_core` em `5a09323`, com as correções genéricas validadas no NextHome: SEO, perfis administrativos, importação de 2FA, dependências de plugins, catálogo/versões e remoção da duplicação de plugins na administração.

## Contratos

- Utilizadores administrativos: modelo configurado pelo site, guard `web`, permissões e perfis do Core.
- 2FA: `Services\Auth\TwoFactorManager` e `Models\TwoFactorAuthentication`. A importação de `cms_account_factors` conserva fatores confirmados; requer APP_KEY original.
- Clientes: package opcional `pcteckserv/cms-customers`, versão major 2 para Core 3. O Core não contém modelos, rotas nem administração de clientes. Não instalar a antiga versão 1.2 com restrição Core 2.
- Contas de clientes: guard `shop`; usar `CustomerIdentity::current(verified: true)` e a API de contactos operacionais do plugin. Nunca identificar contas apenas por coincidência de email.
- Agenda e Imobiliário: manifesto `requires: ["customers"]`; ativação depende de Clientes e este não pode ser desativado enquanto existir dependente ativo.
- Plugins de pagamentos e faturação: integrações opcionais. Não tornar Moloni, PayPal ou Easypay dependências obrigatórias do Core ou do Starter.

## Compatibilidade dos plugins

| Plugin | Base atual disponível | Próxima adaptação |
|---|---|---|
| Clientes | Candidata 2.0.0 no NextHome | Publicar fonte central compatível e validar migrações |
| Agenda | Candidata 0.18.0 no NextHome | Incorporar o contrato Clientes na fonte principal e portar a suite legada |
| Imobiliário | Candidata 0.1.0 no NextHome | Evoluir a partir de Core 3 e Clientes 2 |
| Moloni | Fonte principal anterior | Rever ligações à Loja, permissões e contratos Core 3 |
| PayPal | Fonte principal anterior | Rever provider e dependências da Loja |
| Easypay | Fonte principal anterior | Rever provider, notificações e dependências da Loja |
| Ecommerce | Adaptação pendente | Renomeação futura para Loja, em migração separada |

Não alterar apenas a restrição Composer para declarar compatibilidade. Cada plugin exige testes dos serviços, permissões, webhooks e integrações que utilizar. Não foram executados pagamentos nem chamadas externas de faturação nesta operação.

## Instalações existentes

Antes de migrar um site real: copiar a base de dados, preservar .env/APP_KEY, inventariar plugins, atualizar o lock apenas com versões compatíveis e ensaiar as migrations numa cópia. Tokens e sessões de clientes antigos não são reutilizados. As migrações de clientes pertencem ao plugin Clientes; a migração administrativa de 2FA pertence ao Core.

O `novosite` tinha uma junction diretamente para o Core principal 2.20. Foi fixado numa cópia independente dessa versão antes da troca, sem migrar dados, .env ou plugins desse site. A migração funcional desse projeto ficou fora desta atualização.

Backups do Core/Starter anteriores, bundles Git e plano de aplicação: `D:/WEBSITES_NOVOS/.tools/adopt-novo-main-20261107`. O histórico e os remotos foram preservados. Publicação autorizada: Core `v3.0.0`, Starter `v2.0.0`, NextHome `v1.0.1`; plugins identificados pelas versões nos manifestos, sem tags Git.

## Validação

O Core principal passou os 195 testes (958 asserções). O Starter principal, com apenas Core 3 instalado, passou os 74 testes e o build Vite. A suite JavaScript de geografia do Core passou os cinco testes. Consultar `MIGRACAO-NOVO.md` no Starter para os comandos e limites. Não foi utilizado o browser.
