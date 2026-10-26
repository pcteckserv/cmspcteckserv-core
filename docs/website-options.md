# Opções do site

As Opções gerais incluem os separadores Geral, Contas e registo e Políticas.
O registo público controla a conta partilhada de cliente, sem permitir criar
utilizadores administrativos. A opção de exigir conta no checkout é apresentada
quando o Ecommerce está ativo.

As políticas são textos simples, com parágrafos e quebras de linha preservados.
HTML introduzido nos textos é apresentado como texto, sem executar código.
Os endereços públicos são `/politica-de-privacidade`, `/politica-de-cookies`,
`/termos-e-condicoes` e `/politica-de-reembolso`. Sem texto, devolvem 404.
O painel disponibiliza um iframe HTML para incorporar cada documento numa página.
O início da administração assinala as políticas por preencher. Os termos e
reembolsos apenas entram nesta lista quando o Ecommerce está ativo.

Os menus e o CSS pertencem ao tema de cada site, em `resources/views`,
`resources/css` e `resources/js`. Os editores globais de menus e estilos
foram removidos em 2.16. A migração elimina apenas as opções `website.menus`
e `website.css`; a tabela partilhada `cms_site_options` e as restantes
configurações são preservadas. Não há tabelas exclusivas destes editores.

Os novos valores são guardados em `cms_site_options`, com prefixo `website.`.
Na ausência de uma escolha global, as opções antigas da loja de registo,
conta obrigatória e termos continuam a servir de fallback. Um texto global
explicitamente vazio não recupera os termos antigos. O Ecommerce 0.21 requer
Core 2.12 e passa a exigir termos e reembolsos preenchidos para criar compras.
Executar as migrações do Core para aumentar a capacidade de texto das opções.

Validação: `tests/Standalone/WebsiteTest.php`,
`tests/Standalone/RenderAccountFixturesTest.php` e `tests/Browser/website.spec.cjs`.
