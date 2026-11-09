<dialog id="cms-icon-picker-modal" class="cms-icon-modal" aria-labelledby="cms-icon-picker-title" data-cms-icon-modal>
    <div class="cms-icon-modal__header">
        <h2 id="cms-icon-picker-title" class="h5 mb-0">Selecionar ícone</h2>
        <button type="button" class="btn-close" aria-label="Fechar" data-cms-icon-close></button>
    </div>
    <div class="cms-icon-modal__toolbar">
        <div class="nav nav-pills" role="tablist" aria-label="Biblioteca de ícones">
            @foreach (\Pcteckserv\CmsCore\Support\IconCatalog::libraries() as $key => $library)
                <button type="button" id="cms-icon-tab-{{ $key }}" class="nav-link {{ $loop->first ? 'active' : '' }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="cms-icon-panel" tabindex="{{ $loop->first ? '0' : '-1' }}" data-cms-icon-library="{{ $key }}">{{ $library['label'] }}</button>
            @endforeach
        </div>
        <label for="cms-icon-search" class="form-label mb-0">Pesquisar ícones</label>
        <input type="search" id="cms-icon-search" class="form-control" placeholder="Pesquisar pelo nome do ícone…" autocomplete="off" data-cms-icon-search>
    </div>
    <div id="cms-icon-panel" role="tabpanel" aria-labelledby="cms-icon-tab-bootstrap">
        <p class="text-secondary px-3 mb-2" role="status" aria-live="polite" data-cms-icon-status></p>
        <div class="cms-icon-modal__grid" data-cms-icon-grid></div>
        <div class="cms-icon-modal__pagination">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-cms-icon-previous>Anterior</button>
            <span data-cms-icon-page></span>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-cms-icon-next>Seguinte</button>
        </div>
    </div>
    <div class="cms-icon-modal__footer">
        <button type="button" class="btn btn-outline-danger" data-cms-icon-clear>Remover ícone</button>
        <button type="button" class="btn btn-secondary" data-cms-icon-close>Cancelar</button>
    </div>
</dialog>
<script type="application/json" data-cms-icon-catalog>@json(\Pcteckserv\CmsCore\Support\IconCatalog::libraries())</script>
