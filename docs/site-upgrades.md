# Compatibilidade das atualizações

O Core contém clientes, contas, moradas, grupos e permissões. Nenhum destes
recursos requer o Ecommerce. O nome físico cms_shop_customers mantém-se por
compatibilidade com dados, palavras-passe e chaves estrangeiras anteriores.
Os plugins acrescentam histórico à ficha comum através de CustomerModules.

A atualização não copia páginas, CSS ou JavaScript para o tema de um cliente.
O StudioFranco e o CPL Limpezas mantêm as suas views, rotas e recursos locais.
As rotas legais genéricas do Core são fallback: uma página legal explícita
registada pelo site tem prioridade, mesmo quando as rotas estão compiladas.

Em 2.16 foram removidos os editores globais de menus e estilos. A migração
2026_10_26_160000 remove exclusivamente website.menus e website.css. Não elimina
a tabela cms_site_options nem qualquer tabela de clientes. A operação é
idempotente. Os componentes x-cms-menu e respetivos endereços foram removidos;
um tema que os tivesse adotado teria de passar a definir a sua navegação.
Os dois sites inspecionados não dependem destes componentes.

Para instalações anteriores à centralização dos clientes, as migrações
preservam os IDs, hashes de palavra-passe e moradas existentes. A atribuição
de papéis usa lotes de 500 registos e insertOrIgnore para preservar associações.
As migrações já aplicadas não são repetidas em cada atualização.

O atualizador do Core executa as migrações pendentes e limpa caches. Manter
backup e as constraints do projeto compatíveis com a versão pretendida.
Os repositórios path do ambiente de desenvolvimento não devem fixar a versão
publicada; o atualizador inclui a conversão dos pacotes locais para o GitHub.

Validação automatizada: SharedCustomersTest simula a estrutura anterior de
clientes e confirma credenciais, moradas, autenticação e processamento em lote;
WebsiteTest confirma a limpeza limitada e a prioridade das rotas legais antes
e depois da compilação. As suites dos sites podem ser executadas com o Core
atual, base de dados SQLite em memória e dependências de teste instaladas.

## Verificação dos sites em 26/10/2026

No ambiente local, os testes funcionais, de administração e de páginas públicas
passaram no StudioFranco e no CPL Limpezas com o Core atualizado. As suites
completas registaram 65/66 e 50/51 testes aprovados, respetivamente. O único
erro em cada site é o teste de empacotamento que lê um path repository antigo
do plugin de formulários, apontando para uma pasta Windows inexistente.
Este problema dos manifests locais é independente da remoção dos editores e
requer instalar/corrigir as dependências locais antes de compilar esses sites.
Não foram alterados ficheiros ou bases de dados desses projetos.
