# Regras do Core partilhado

Consultar também `../AGENTS.md`: inventário das capacidades e fontes de verdade do Core, Clientes e Realstate. A identidade dos clientes pertence ao plugin Clientes; não criar tabelas paralelas em Starters.

O Core é uma dependência estável de vários sites. Não o alterar para resolver
pedidos visuais, conteúdos, regras de negócio ou conveniências de um único site.
Essas alterações pertencem ao Starter ou ao plugin responsável.

Uma alteração no Core exige um defeito comum confirmado, uma correção de
segurança, uma necessidade de compatibilidade demonstrada ou uma evolução
explicitamente autorizada pelo utilizador. Quando uma correção estiver autorizada
na conversa, concluí-la nesse âmbito sem pedir nova aprovação por cada ficheiro
necessário. Não generalizar essa autorização para outros trabalhos ou sites.

Antes de publicar:

- Reproduzir a falha e identificar quais as versões e sites afetados.
- Fazer a menor correção genérica, preservando os contratos existentes.
- Trabalhar em cópias isoladas; não ler nem alterar `.env`, dados, dependências
  ou vendors dos sites originais sem autorização específica.
- Testar a correção, a recusa de operações incompatíveis e a preservação dos
  ficheiros e versões existentes. Uma regressão nova ou verificação obrigatória
  sem resultado satisfatório bloqueia a publicação. Falhas preexistentes devem
  ser reproduzidas na origem e documentadas, sem apresentar a suite como aprovada.
- Documentar alterações, validação, limites e passagem das instalações antigas.
- Regenerar e verificar o manifesto de integridade antes da tag.
- Criar uma tag nova; nunca mover tags publicadas nem anunciar uma versão sem
  instalar os seus ficheiros.

Atualizações de Core:

- Nunca migrar automaticamente entre versões principais.
- Preservar a linha de manutenção dos sites Core 2; mudanças de linha exigem
  um plano de migração e validação dos plugins.
- Oferecer versões estáveis elegíveis; limpar ou ignorar candidatas incompatíveis
  guardadas em cache. Não anunciar sucesso quando a consulta falha.
- Preparar Composer fora da instalação, preservando Laravel e plugins existentes.
  Não usar `--with-all-dependencies` como atalho para atualizar o Core.
- Confirmar ficheiros, identidade e metadados; recusar estados ambíguos em vez
  de adivinhar ou alterar apenas a versão declarada.
- Não manter variantes do mesmo atualizador nos Starters. Uma ponte temporária
  deve reutilizar código genérico, ter âmbito e remoção documentados e ser testada.

A linha principal é Core 3; a manutenção 2.9 pertence a `maintenance/2.9`.
Não transplantar funcionalidades Core 3 para sites Core 2 através de aliases
ou relaxamento de restrições. Testes locais não certificam todos os alojamentos,
plugins, integrações externas ou futuras versões.

## Verificações obrigatórias em cada alteração do Core

Estas verificações aplicam-se também a patches pequenos e a ramos de manutenção.
Não publicar por urgência nem dispensar um teste porque a alteração parece simples.
Executar em cópias privadas e conservar os relatórios com a versão e o commit testados.

1. Reproduzir o caso reportado antes da correção. Rever o diff completo e executar
   `git diff --check` e o lint de todos os PHP alterados, incluindo exportadores e templates.
2. Regenerar `php tools/build-release-manifest.php <versão>` e executar
   `php tools/verify-release.php . <versão>`. Verificar também o conteúdo exportado
   ou arquivado que será efetivamente instalado; não apenas o diretório de trabalho.
   Verificar também um arquivo Git canónico, com `git -c core.autocrlf=false archive`,
   e diferenças LF/CRLF. Um ZIP exportado no Windows pode ocultar um hash incorreto
   para Linux. Manter a normalização dos scripts de texto e os atributos Git coerentes.
3. Core 3: preparar um host novo com `php tools/prepare-test-host.php <pasta-nova>`
   e instalar as suas dependências fora dos sites. Definir `CMS_CORE_TEST_APP` para
   esse host e `CMS_COMPOSER_BINARY` para o Composer real. Executar a suite:
   `php <host>/vendor/phpunit/phpunit/phpunit -c phpunit.xml --no-coverage --fail-on-warning --fail-on-notice --fail-on-deprecation`.
   Cobrir PHP 8.3, 8.4 e 8.5 com SQLite e uma base MySQL/MariaDB descartável.
   Usar apenas nomes `cms_core_test_*`, em loopback; nunca dados de clientes.
4. Core 3: executar `node --test tests/Js/*.test.mjs`. Nas outras linhas, executar
   as suites JavaScript existentes quando aplicável. O workflow
   `.github/workflows/compatibility.yml` do Core 3 executa automaticamente a matriz
   PHP/SQLite/MySQL, integridade, lint, Composer real e JavaScript em pushes e PRs.
   Não confundir verificação local com resultado remoto: confirmar o resultado do CI
   quando disponível e não anunciar um CI aprovado sem o observar.
5. Nos ramos Core 2, usar um host e bootstrap compatíveis com essa linha; não
   instalar Core 3 para fazer os testes passar. Executar a suite histórica e, de
   forma obrigatória, todos os testes de atualização, seleção de versões, plugins
   path e transação Composer dessa linha. Falhas de referência só são aceitáveis
   se reproduzidas no commit anterior no mesmo host e documentadas individualmente.
6. Em alterações ao atualizador, testar com Composer real: Core antigo para a
   versão candidata; uma segunda atualização; Git sem `.git`; ZIP; plugins em
   `packages` com install-path vendor antigo; caminhos de desenvolvimento ausentes;
   temporários Composer; plugin que exige Core novo; remoção e dependentes; modos
   com e sem dev; integridade inválida; conflito de versões; falha pós-troca e
   reposição. Confirmar as versões Laravel/plugins e os ficheiros preservados.
   Não substituir esses testes por mocks dos comandos Composer.
7. Se houver passagem FTP, gerar um pacote novo e testar os seus ficheiros e hashes,
   autoload autoritativo antigo, chave incorreta, envio incompleto/alterado, versão
   Core errada, conclusão e eliminação do finalizador. Não enviar segredos ou URLs
   privadas de recuperação para o Git.
8. Testar as cópias de CGLimpezas e MaxiHome, cada uma na
   sua linha Core. StudioFranco e Turkesa usam outro Core e não pertencem a esta
   matriz, por indicação do utilizador em 17/11/2026.
   Cobrir página pública, login, autenticação, CSRF, admin/updates,
   admin/plugins e rotas dos plugins utilizados. Verificar assets e hashes/HEAD/status
   dos originais. Acrescentar novos sites a esta matriz quando forem criados.
9. Registar resultados, falhas de referência e limites na documentação da versão.
   Uma verificação obrigatória pendente ou uma falha nova bloqueia a tag e o push.
   Depois de corrigir uma falha, repetir os testes afetados e os checks exigidos;
   não repetir suites aprovadas sem uma nova alteração ou preocupação concreta.

Estas suites são executadas no desenvolvimento antes da publicação. O atualizador
em produção executa as validações de plano, compatibilidade, integridade e instalação
preparada; não corre PHPUnit nem altera o ambiente de produção para testar.
