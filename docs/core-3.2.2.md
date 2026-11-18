# Core 3.2.2

- A lista de atualizações ignora entradas antigas do catálogo que não pertencem aos pacotes instalados/ativos do site. O catálogo de plugins continua acessível no seu menu próprio.
- Criação de utilizadores com email identificado, preenchimento automático desativado e novas palavras-passe; os campos de acesso ficam editáveis após foco explícito. Gestores de palavras-passe externos podem aplicar políticas próprias.
- Um Starter pode alargar uma restrição Composer já existente, sem acrescentar dependências nem restringir as versões anteriores. O atualizador preserva sempre o Composer, lock, Laravel, Core e plugins instalados. Alterações de autoload, dependências novas ou incompatíveis continuam recusadas.
- O inventário em AGENTS identifica as responsabilidades do Core e do plugin Clientes para evitar duplicar modelos e tabelas nos sites.

## Validação

Runtime `3e32c70`, arquivo Git canónico com LF, manifesto 3.2.2 e dependências instaladas por Composer em seis hosts novos. PHP 8.3.36, 8.4.27 e 8.5.12: SQLite e MariaDB 11.4.8, 319 testes e 1615 asserções em cada combinação, sem avisos ou falhas; oito testes JavaScript aprovados. Na matriz MariaDB foi configurado explicitamente o executável `mariadb-dump` e repetida a suite completa, depois de identificar a falta dessa configuração no primeiro ensaio. A normalização final preserva o JavaScript anterior byte a byte; a proteção do formulário não exige recompilar os assets.

As falhas do formulário, entradas antigas de atualizações e restrições alargadas foram reproduzidas na versão 3.2.1. As correções focadas passaram antes da matriz completa. A cópia da CG Limpezas passou 143 testes (131 aprovados, 12 ignorados); um primeiro ensaio com caches de views antigos teve bloqueios de renomeação no Windows e foi repetido com uma pasta de compilação isolada. O navegador confirmou login e edição dos campos de acesso com os assets antigos e largura de 390 px. A instalação original da CG Limpezas mantém a sua base e os seus ficheiros.

Na MaxiHome são validadas também as novas áreas, documentos de contactos, conversão de clientes, instalação dos snapshots e compilação. Os relatórios de distribuição do Starter descrevem esses resultados. Ordem de atualização: Core 3.2.2, Clientes 1.6.0, Realstate 0.47.0 e Starter MaxiHome 1.40.0. Atualizar primeiro os plugins evita conflitos com as restrições de Starters mais antigos. Os campos antigos de área não são recalculados.

Não foram executadas integrações externas com contas de produção, nem certificadas todas as versões de gestores de palavras-passe. Os hosts e bases usados na matriz são privados e descartáveis.
