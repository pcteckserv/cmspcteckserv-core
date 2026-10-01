# Selector de media

O selector de media reutilizável permite escolher uma imagem existente da biblioteca ou carregar até 20 imagens de uma vez através do Media Manager. Pode selecionar os ficheiros no botão de carregamento ou arrastar imagens para a modal. Depois do carregamento, selecione a imagem que pretende associar ao campo.

Utilização base:

```blade
<x-cms-media-picker
    name="image_media_id"
    label="Imagem"
/>
```

Também pode receber valor inicial e texto de ajuda:

```blade
<x-cms-media-picker
    name="hero_image_media_id"
    id="hero_image_media_id"
    label="Imagem de destaque"
    :value="$model->hero_image_media_id"
    help="Selecione ou carregue uma imagem de destaque."
/>
```

O componente grava o ID do registo de media no input indicado por `name`. A modal é carregada uma única vez no layout administrativo do CMS Core.

As rotas usadas pelo selector mantêm as permissões do Media Manager:

- `media.view` para consultar a biblioteca;
- `media.upload` para carregar novas imagens.

Para um campo que aceite várias imagens, defina `multiple`. O formulário recebe uma lista de IDs no campo indicado por `name`:

```blade
<x-cms-media-picker
    name="media_ids"
    label="Imagens"
    help="Escolha uma ou várias imagens da biblioteca do CMS."
    button-label="Escolher imagens"
    :multiple="true"
    clearable
/>
```

Na modal, selecione as imagens pretendidas e confirme em **Adicionar imagens**. O selector envia os IDs como `media_ids[]`; valide sempre a lista no servidor.

## Imagem de fallback partilhada

O Core disponibiliza uma imagem comum em `vendor/cms-core/images/placeholder.webp`. A configuração `cms-core.assets.placeholder_image` contém o respetivo caminho público; funcionalidades e plugins podem gerar a URL com:

```blade
{{ asset(config('cms-core.assets.placeholder_image', 'vendor/cms-core/images/placeholder.webp')) }}
```

O Core também devolve esta imagem quando um ficheiro de imagem da biblioteca não está disponível. Documentos e outros ficheiros mantêm o seu tratamento próprio.
