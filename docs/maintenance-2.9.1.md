# Core 2.9.1 — manutenção do StudioFranco

Esta entrega deriva de `v2.9.0`, no ramo `maintenance/2.9`. Não migra clientes,
autenticação, plugins ou dados para Core 3 e não altera os projetos dos sites.

- Preserva a view original ao inserir os avisos de cookies, administração e manutenção.
- Impede que o atualizador execute Composer para uma versão principal diferente.
- Restringe a instalação de plugins a dependências e alterações mínimas.
- Cria um catálogo vazio quando não está configurado um repositório de plugins.
- Inclui um manifesto verificável dos ficheiros da distribuição.

Validação: 21 testes focados do Core aprovados, 71 asserções; 98 testes do
StudioFranco aprovados, 615 asserções, numa cópia descartável com os plugins
históricos Core 2 e as origens locais de deploy reconstruídas. Passaram também
os testes comuns das rotas públicas e administrativas. A suite antiga integral
do Core contém expectativas pré-existentes ligadas ao host e não é apresentada
como totalmente aprovada. A auditoria completa está na linha Core 3, em
`docs/compatibility-audit-2026-11-15.md`.

Não foram modificados ficheiros, configurações, vendor ou dados do StudioFranco.
O repositório antigo declarado pelo projeto não esteve acessível nesta sessão.
Esta tag é disponibilizada no repositório atual `pcteckserv/cmspcteckserv-core`;
publicá-la não corrige a associação antiga do site. Uma instalação da linha 2.9
deve selecionar explicitamente essa linha, porque `2.*` aceita outras versões 2.x.

Verificação dos ficheiros, sem Laravel, base de dados ou `.env`:

```sh
php tools/verify-release.php . 2.9.1
```

O manifesto verifica integridade, não a identidade criptográfica do autor. As
proteções transacionais de deploy do Core 3 não foram transplantadas para Core 2.
