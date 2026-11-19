# Core 3.5.2 — paginação de media

A biblioteca de media usa explicitamente a paginação Bootstrap partilhada do painel. A paginação predefinida do Laravel gerava setas SVG cujas classes Tailwind não tinham estilos neste painel, deixando-as sobredimensionadas. Conservam-se os filtros e os endereços das páginas. O espaço inferior mantém os controlos acessíveis junto ao botão flutuante de ajuda em mobile.

A regressão foi executada antes da alteração: falhou por ausência da paginação Bootstrap. Depois da correção passou, com 1 teste e 6 asserções. Os oito testes JavaScript do Core passaram. O lint dos PHP alterados, o diff e a integridade do manifesto 3.5.2 foram verificados.

Matriz local aprovada: PHP 8.3.36, 8.4.27 e 8.5.12, cada um com SQLite e MariaDB 11.4.8. Cada combinação executou 349 testes e 1798 asserções, sem falhas, erros ou omitidos, com os flags estritos de PHPUnit e Composer real. Os hosts são privados; as bases `cms_core_test_352_*` usam apenas o servidor descartável em loopback.

Os quatro sites privados MaxiHome, CG Limpezas, CPL e Starter passaram página pública, login, recusa de convidados, CSRF, atualizações, plugins, rotas dos módulos usados e assets. O browser confirmou Clientes e paginação de media a 1440, 393 e 320 px, incluindo navegação para a segunda página com filtros; não registou erros JavaScript. Os originais MaxiHome e CG Limpezas conservaram o HEAD e o estado inicial.

Integridade aprovada no diretório de trabalho, no arquivo Git canónico com LF e na cópia dos mesmos ficheiros de texto convertida para CRLF. Relatórios, XML e capturas em `output/core352-validation`, fora dos repositórios. Ensaios exploratórios interrompidos e uma cópia inicial de temporários em uso foram substituídos por hosts independentes; a matriz acima corresponde às execuções finais aprovadas. Esta versão não modifica o atualizador, as migrations, os contratos dos plugins ou os dados dos sites. Validação local não representa uma atualização já instalada no alojamento; o resultado remoto do CI é verificado separadamente.

A correção do catálogo CPL é distribuída no Starter CPL 1.2.2, que deixa de forçar uma pasta local e inclui Core 3.5.2 para novas instalações. A organização do cabeçalho e dos filtros de Clientes pertence ao plugin Clientes 1.7.1. O Starter genérico 2.1.2 também passa a usar Core 3.5.2; já respeitava a origem configurada, confirmada com um repositório Git sintético. Não é necessário substituir o Starter de outros sites por uma cópia do genérico.
