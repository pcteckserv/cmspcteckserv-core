# Selector de media

O selector de media reutilizável permite escolher uma imagem existente da biblioteca ou carregar uma nova através do Media Manager.

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
