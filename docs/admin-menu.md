# Menu administrativo dos plugins

O Core fornece `Pcteckserv\CmsCore\Support\Navigation\AdminMenuRegistry` como singleton.
Cada plugin regista os seus itens no `boot()` do respetivo service provider:

```php
app(AdminMenuRegistry::class)->register(
    key: 'contact-forms',
    label: 'Formulários de contacto',
    route: 'admin.contact-forms.index',
    permission: 'contactforms.forms.view',
    plugin: 'contact-forms',
    active: 'admin.contact-forms.*',
    order: 100,
);
```

`plugin` corresponde ao slug utilizado na gestão de plugins. O item só aparece
quando a rota existe, o utilizador tem a permissão e o plugin está ativo.
A visibilidade é avaliada em cada renderização, sem cache do estado de ativação.
As rotas e os controllers continuam responsáveis pela autorização de acesso.
Não é necessário alterar o HTML do menu ao instalar novos plugins.
