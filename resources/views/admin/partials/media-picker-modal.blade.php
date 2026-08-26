<div class="cms-media-picker" hidden data-cms-media-picker-modal>
    <div class="cms-media-picker__dialog" role="dialog" aria-modal="true" aria-labelledby="cms-media-picker-title">
        <div class="cms-media-picker__header">
            <div>
                <h2 class="h5 mb-1" id="cms-media-picker-title">Biblioteca de media</h2>
                <p class="text-secondary small mb-0">Selecione uma imagem existente ou carregue uma nova.</p>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-cms-media-picker-close>Fechar</button>
        </div>

        <div class="cms-media-picker__toolbar">
            <input class="form-control" type="search" placeholder="Pesquisar imagens" data-cms-media-picker-search>
            <label class="btn btn-outline-primary mb-0 cms-media-picker__upload-button" title="Carregar imagem" aria-label="Carregar imagem">
                <span aria-hidden="true">&#8593;</span>
                <input class="visually-hidden" type="file" accept="image/*" data-cms-media-picker-file>
            </label>
        </div>

        <div class="cms-media-picker__status text-secondary small" data-cms-media-picker-status></div>
        <div class="cms-media-picker__grid" data-cms-media-picker-grid></div>
    </div>
</div>
