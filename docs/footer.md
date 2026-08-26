# Footer público

O footer público global do CMS Core é renderizado através do componente Blade:

```blade
<x-cms-footer />
```

O componente lê automaticamente as opções globais do CMS, incluindo o título do site, texto de copyright, cores, espaçamento, estado ativo/inativo e crédito PCTECKSERV.

O configurador administrativo está disponível em:

```text
/admin/footer
```

No campo de logótipo é possível abrir a biblioteca de media, pesquisar imagens existentes ou carregar uma nova imagem. A selecção grava o `footer_pcteckserv_logo_media_id`, mantendo o path fallback apenas como alternativa.

Em layouts públicos que já incluem este componente, as páginas, módulos e plugins não devem voltar a inserir `<x-cms-footer />` manualmente para evitar footers duplicados.

O logótipo PCTECKSERV é resolvido primeiro através de `footer_pcteckserv_logo_media_id`. Se não existir um registo válido no Media Manager, é usado o path relativo `footer_pcteckserv_logo_path` no disco público. Se nenhum ficheiro estiver acessível, o componente mostra o texto `PCTECKSERV`.
