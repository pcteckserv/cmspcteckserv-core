# Endereços do site e migrações

O endereço público ativo é `config('app.url')`, normalmente definido por `APP_URL`.
As opções gerais e o URL base de SEO são apresentados apenas para consulta.
Guardar configurações administrativas nunca altera o ficheiro `.env`.

Esta alteração exige uma versão principal do Core: deixa de ser possível mudar
o domínio pelas opções gerais ou por um URL base de SEO independente. A publicação
3.0.0 introduz esta mudança; os sites devem atualizar a restrição Composer do Core.

`Support\SiteUrl` guarda referências internas como caminhos relativos à instalação
e gera URLs absolutos com o endereço configurado. A regra `Rules\SiteLink` aceita
caminhos internos ou URLs HTTP/HTTPS sem credenciais, barras invertidas ou
caracteres de controlo. URLs externos mantêm o respetivo domínio.

O favicon, as imagens SEO, os canonicals e a ligação do sitemap usam este mecanismo.
Os valores antigos de `site_url` e `seo_base_url` na base de dados são preservados
como referências para reconhecer o domínio anterior, incluindo porta e subdiretório.
Não são o endereço ativo. Não eliminar essas referências antes de converter os
URLs legados. Os aliases de configuração antigos também podem identificar essas
referências, mas não substituem `config('app.url')`.

Os plugins Loja 0.48.0 e Clientes 1.3.0 requerem a API do Core 3.0.0. As páginas
de termos e privacidade passam a aceitar caminhos internos e a acompanhar o
domínio atual. Os registos históricos de consentimento e encomendas conservam o
URL efetivamente aceite. Atualizar Core e plugins em conjunto; a atualização
destas packages não atualiza automaticamente os sites instalados.

Após uma migração, atualizar o `APP_URL`, limpar as caches da aplicação e recriar
a cache de configuração quando utilizada. Copiar os media, recriar a ligação
`public/storage` e garantir que `public/hot` não existe em produção.

Ligações em HTML livre, conteúdo específico do site, URLs externos e configurações
de serviços externos não são substituídos automaticamente. Um domínio antigo que
não esteja registado nas referências conhecidas exige revisão explícita.
