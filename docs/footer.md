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

Em layouts públicos que já incluem este componente, as páginas, módulos e plugins não devem voltar a inserir `<x-cms-footer />` manualmente para evitar footers duplicados.

O logótipo PCTECKSERV é resolvido primeiro através de `footer_pcteckserv_logo_media_id`. Se não existir um registo válido no Media Manager, é usado o path relativo `footer_pcteckserv_logo_path` no disco público. Se nenhum ficheiro estiver acessível, o componente mostra o texto `PCTECKSERV`.
