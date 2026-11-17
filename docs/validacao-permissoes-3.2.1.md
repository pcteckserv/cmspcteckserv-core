# Permissões de plugins ativos — 3.2.1

Correção pedida pelo utilizador: os formulários de roles e permissões diretas mostram apenas permissões Core e de plugins instalados e ativos. As atribuições ocultas são preservadas ao guardar, permitindo reativar o plugin sem perder a configuração. O superadmin mantém acesso integral. Não muda a decisão de autorização das rotas nem remove registos históricos.

## Reprodução e compatibilidade

O teste novo falha no Core de referência anterior à correção (3.1.12, PermissionSynchronizer idêntico ao 3.2.0), onde a permissão do plugin desativado continua visível. Passa na candidata; verifica também guardar, reativar, desinstalar e superadmin. Uma única alteração funcional em PermissionSynchronizer; sem migrations, mudanças de Composer, Laravel ou contratos públicos.

## Validação local — 17/11/2026

- Seis hosts novos e isolados: PHP 8.3.36, 8.4.27 e 8.5.12 × SQLite/MariaDB 11.4.8. Cada execução: 317 testes e 1605 asserções, sem avisos, notices ou depreciações. Composer real nos testes de instalação/atualização; bases descartáveis cms_core_test_* em loopback.
- JavaScript: 8 testes aprovados.
- Cópias isoladas de MaxiHome e CG Limpezas atualizadas para 3.2.1 com Composer real, preservando os restantes pacotes. Página pública, login, recusa de convidados, CSRF, admin/updates, admin/plugins, backups, rotas dos plugins e assets aprovados; backups também a 390 px sem transbordamento horizontal.
- Originais preservados; não foram lidos os seus .env, vendors ou dados, nem alterada a base da CG Limpezas. StudioFranco/Turkesa fora da matriz por indicação do utilizador.
- Lint PHP/Blade, diff e manifesto verificados. Os arquivos canónico LF e Windows são verificados antes da publicação; resultado CI registado após execução remota.

## Atualização

Patch da linha Core 3, através do atualizador. Não migra Core 2, não instala/ativa plugins, não exige alterar atribuições existentes. Plugins sem metadados de proprietário no registo de permissões não permitem inferir a origem; esta correção respeita o contrato de registo existente. Não certifica alojamentos nem plugins de terceiros não ensaiados.
