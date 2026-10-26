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

Configurações → Menus permite criar, editar e eliminar menus, com ligações,
ordenação por arrasto ou botões, submenus e abertura num novo separador.
O catálogo permite pesquisar e adicionar várias ligações de uma vez. Os plugins
registam fontes de ligações em `MenuLinkRegistry`; o Ecommerce disponibiliza
loja, carrinho, promoções, produtos, categorias, etiquetas e marcas.
Pode duplicar ramos, remover seleções, anular operações e pré-visualizar a
estrutura antes de guardar. Email (`mailto:`), telefone (`tel:`), título,
descrição, classes CSS e relação da ligação são opcionais. Cada localização
Cabeçalho/Rodapé recebe um menu; atribuir outro mantém o anterior como personalizado. As localizações
Cabeçalho e Rodapé são usadas por temas que renderizem o componente Blade:

```blade
<x-cms-menu slug="principal" />
<x-cms-menu location="header" />
<x-cms-menu location="footer" />
```

O endereço `/menus/{identificador}` e o iframe fornecido no painel permitem
usar um menu numa página HTML. As localizações não substituem automaticamente
os menus próprios de temas existentes. URLs javascript/data e ciclos na
hierarquia são rejeitados no servidor.

Configurações → Estilo guarda CSS adicional em `/site-custom.css`, carregado
depois do CSS do tema nas respostas HTML públicas que contenham `</head>` e
usem o middleware web. Não altera os ficheiros de origem do tema nem o painel.
Limpar e guardar o campo remove as personalizações.

Os novos valores são guardados em `cms_site_options`, com prefixo `website.`.
Na ausência de uma escolha global, as opções antigas da loja de registo,
conta obrigatória e termos continuam a servir de fallback. Um texto global
explicitamente vazio não recupera os termos antigos. O Ecommerce 0.21 requer
Core 2.12 e passa a exigir termos e reembolsos preenchidos para criar compras.
Executar as migrações do Core para aumentar a capacidade de texto das opções.

Validação: `tests/Standalone/WebsiteTest.php`,
`tests/Standalone/RenderAccountFixturesTest.php` e `tests/Browser/website.spec.cjs`.
