# Países e regiões · Core 2.13.0

O Core fornece o catálogo base local de 249 países em pt-PT, os nomes personalizados,
as regiões importadas e os aliases usados por moradas, impostos e envios. Os
formulários não contactam a API. A consulta funciona sem Loja nem importações.

Em **Configurações → Países e regiões** (`/admin/geography`), selecionar os países
e usar **Importar regiões**. A importação CountriesNow é explícita, com URL fixa,
timeouts, limite de resposta e validação. Uma falha preserva os dados anteriores.
O editor permite renomear países/regiões e acrescentar regiões; códigos e aliases
anteriores são preservados. As alterações ficam no registo de atividade.

Permissões: `core.geography.view` para consultar e `core.geography.update` para
importar/editar. As alterações exigem autenticação, CSRF e limite de pedidos;
a edição verifica a revisão do país para impedir alterações concorrentes.

## Atualização

Atualizar o Core para 2.13.0, Clientes para 1.2.0 e Loja para 0.46.0 quando instalada.
Executar `php artisan migrate --force` e `php artisan cms:permissions-sync`.
A migration cria `cms_geography` e copia os dados de `cms_shop_geography` quando
existem, incluindo nomes, regiões, aliases, fontes, revisões e datas. A tabela antiga
é preservada, mas as versões novas leem e escrevem apenas no catálogo do Core.
O rollback copia os dados atuais para a tabela antiga quando esta existe.
Não recuar o Core mantendo as novas versões dos plugins, que dependem de 2.13.0.

## Utilização por módulos

`Geography\CountryCatalog` é scoped ao pedido e disponibiliza países, regiões,
resolução de aliases e payload para formulários. Importações e edições invalidam
o snapshot através de `forget()`. `CountryNames::all()` fornece o catálogo base,
independentemente das importações. `CountryRegions::groups()` agrupa os países.

Clientes usa o catálogo completo e valida os códigos ao guardar moradas. O select
mostra nomes e guarda códigos ISO. Com regiões importadas, o módulo JavaScript
partilhado apresenta um select de regiões; sem importações ou sem JavaScript,
aceita texto. Valores antigos são apresentados sem reescrever a base de dados.

Os países de venda pertencem exclusivamente à Loja. Uma morada guardada pode estar
fora desses países; a Loja verifica a elegibilidade comercial durante a compra.
O Core não consulta configurações ou implementações internas de plugins.
