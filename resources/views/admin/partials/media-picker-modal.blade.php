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
                <svg class="bi bi-upload" aria-hidden="true" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                    <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                </svg>
                <input class="visually-hidden" type="file" accept="image/*" data-cms-media-picker-file>
            </label>
        </div>

        <div class="cms-media-picker__status text-secondary small" data-cms-media-picker-status></div>
        <div class="cms-media-picker__grid" data-cms-media-picker-grid></div>
    </div>
</div>
