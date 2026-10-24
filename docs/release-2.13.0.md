# Core v2.13.0 — perfis profissionais

Fotografia e apresentação profissional em O meu perfil, guardadas centralmente
por utilizador CRM em cms_user_profiles. A Agenda e futuros plugins reutilizam
esses dados. O carregamento valida imagem/tamanho, exige a palavra-passe atual
e permite remover a fotografia. SVG não é aceite. A atualização não altera o
perfil de outro utilizador.

Validação antes da publicação:
- Composer validate --strict --no-check-publish: aprovado.
- Suite independente Core: 62 testes, 577 asserções, aprovada.
- Suite principal Core: 150 testes, 143 aprovados; as mesmas quatro falhas e três
  erros do registo de v2.12.0, comparados por nome de teste. Sem novas ocorrências.
- Plugins relacionados: 230 testes, 1705 asserções, aprovados.
- Agenda: 14 testes Playwright aprovados, incluindo mobile.

Aplicar a migration 2026_10_24_190000_create_user_profiles.php antes de atualizar
a Agenda para 0.7.0. As imagens usam o disco public e a ligação storage:link.
Consultar docs/professional-profiles.md para o contrato usado pelos plugins.