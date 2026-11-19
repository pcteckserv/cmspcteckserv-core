# Core 3.6.0 — taxas de IVA configuráveis

Opções gerais inclui IVA: uma taxa por linha, entre 0 e 100, com até duas casas decimais. Não são presumidas taxas, localização fiscal ou motivos de isenção. Uma lista vazia impede selecionar IVA em novos acordos que o exijam. Alterar as opções não modifica documentos ou negócios históricos.

`Support\VatRates::rates()` devolve as taxas normalizadas e `parse()` valida o texto. As opções usam `SiteOptions`, as permissões existentes `core.site-options.view/update`, autenticação e CSRF. Não há novas tabelas nem alterações no atualizador.

O teste `VatRatesTest` verifica interface, persistência, normalização, taxa inválida e remoção da lista. A matriz completa e o ensaio da distribuição são registados no relatório de comissões desta entrega; não se declara uma instalação em produção a partir da publicação em Git.
