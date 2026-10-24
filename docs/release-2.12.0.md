# Core v2.12.0 — 24-10-2026

Contas e registo nas Opções gerais, políticas editáveis e incorporáveis, menus do site com hierarquia, CSS personalizado e alertas administrativos de políticas em falta quando a Loja está ativa. Inclui integração das opções globais na área de clientes.

## Validação

- Composer: `validate --strict --no-check-publish` aprovado; manifesto sem versão fixa.
- Core standalone: 47 testes, 436 asserções, aprovados.
- Navegador Core: 5 testes aprovados (conta/QR, menus e opções gerais).
- Suite principal Core: 150 testes, 143 aprovados; 4 falhas e 3 erros. Comparação com o commit anterior fcee297, isolado a partir de git archive: mesmos 150 testes, 143 aprovados e exatamente as mesmas sete ocorrências, sem novas falhas.
- Ocorrências anteriores: três asserções de formulários AdminAccessRulesTest, fallback SeoModuleTest, e configuração/mocks do site em InstalledPluginUpdatesTest, PluginRepositoryConfigurationTest e UpdatesManagementTest. Não foram alterados esses testes para ocultar resultados.

Aplicar a migration `2026_10_24_120000_expand_site_option_content.php` no destino e limpar as vistas após atualizar. Ver `docs/website-options.md`.