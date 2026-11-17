# Core 3.2.2

- A lista de atualizações ignora entradas antigas do catálogo que não pertencem aos pacotes instalados/ativos do site. O catálogo de plugins continua acessível no seu menu próprio.
- Criação de utilizadores com email identificado, preenchimento automático desativado e novas palavras-passe; os campos de acesso ficam editáveis após foco explícito. Gestores de palavras-passe externos podem aplicar políticas próprias.
- Um Starter pode alargar uma restrição Composer já existente, sem acrescentar dependências nem restringir as versões anteriores. O atualizador preserva sempre o Composer, lock, Laravel, Core e plugins instalados. Alterações de autoload, dependências novas ou incompatíveis continuam recusadas.
- O inventário em AGENTS identifica as responsabilidades do Core e do plugin Clientes para evitar duplicar modelos e tabelas nos sites.

As validações de publicação e os limites de passagem serão registados depois da matriz local. A CG Limpezas mantém a sua base; a validação usa apenas cópias isoladas.
