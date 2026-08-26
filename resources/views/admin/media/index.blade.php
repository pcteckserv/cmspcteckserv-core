@extends('cms-core::admin.layouts.app')

@php
    $formatBytes = function (?int $bytes): string {
        $bytes = max(0, (int) $bytes);
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1, ',', ' ').' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', ' ').' MB';
        }

        return number_format($bytes / 1024, 1, ',', ' ').' KB';
    };
@endphp

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Media</h1>
            <p class="text-secondary mb-0">Gestão centralizada de imagens e documentos.</p>
        </div>
        <button class="btn btn-primary" type="button" data-cms-media-open-upload>Upload de ficheiros</button>
    </div>

    @if (session('cms_media_success'))
        <div class="alert alert-success">{{ session('cms_media_success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="cms-stat"><span>{{ number_format($stats['count'], 0, ',', ' ') }}</span><small>ficheiros</small></div></div>
        <div class="col-6 col-xl-3"><div class="cms-stat"><span>{{ $formatBytes($stats['size']) }}</span><small>ocupados</small></div></div>
        <div class="col-6 col-xl-3"><div class="cms-stat"><span>{{ $formatBytes($stats['saved']) }}</span><small>poupados</small></div></div>
        <div class="col-6 col-xl-3"><div class="cms-stat"><span>{{ number_format($stats['pending'], 0, ',', ' ') }}</span><small>por optimizar</small></div></div>
    </div>

    <form class="bg-white border rounded p-3 mb-4" method="GET" action="{{ route('admin.media.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="media-q">Pesquisar</label>
                <input class="form-control" id="media-q" name="q" value="{{ request('q') }}" placeholder="Nome, título ou texto alternativo">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="media-type">Tipo</label>
                <select class="form-select" id="media-type" name="type">
                    <option value="">Todos</option>
                    <option value="image" @selected(request('type') === 'image')>Imagens</option>
                    <option value="document" @selected(request('type') === 'document')>Documentos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="media-extension">Extensão</label>
                <input class="form-control" id="media-extension" name="extension" value="{{ request('extension') }}" placeholder="jpg, pdf">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="media-sort">Ordenar</label>
                <select class="form-select" id="media-sort" name="sort">
                    <option value="">Mais recentes</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Mais antigos</option>
                    <option value="name_asc" @selected(request('sort') === 'name_asc')>Nome A-Z</option>
                    <option value="name_desc" @selected(request('sort') === 'name_desc')>Nome Z-A</option>
                    <option value="size_desc" @selected(request('sort') === 'size_desc')>Maior tamanho</option>
                    <option value="size_asc" @selected(request('sort') === 'size_asc')>Menor tamanho</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit">Filtrar</button>
            </div>
        </div>
        <div class="mt-3 d-flex flex-wrap gap-2">
            <a class="btn btn-sm {{ request()->boolean('deleted') ? 'btn-outline-secondary' : 'btn-secondary' }}" href="{{ route('admin.media.index') }}">Biblioteca</a>
            <a class="btn btn-sm {{ request()->boolean('deleted') ? 'btn-secondary' : 'btn-outline-secondary' }}" href="{{ route('admin.media.index', ['deleted' => 1]) }}">Eliminados</a>
        </div>
    </form>

    <section class="cms-media-dropzone mb-4" data-cms-media-upload-url="{{ route('admin.media.store') }}">
        <input class="visually-hidden" type="file" multiple data-cms-media-file-input>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <strong>Arraste ficheiros para aqui</strong>
                <div class="text-secondary small">JPG, PNG, WebP, GIF e PDF. SVG bloqueado por defeito.</div>
            </div>
            <button class="btn btn-outline-primary" type="button" data-cms-media-select-files>Seleccionar ficheiros</button>
        </div>
        <div class="cms-media-upload-results mt-3" data-cms-media-upload-results></div>
    </section>

    <div class="cms-media-grid">
        @foreach ($mediaItems as $media)
            <article class="cms-media-card bg-white border" data-cms-media-item data-url="{{ $mediaService->url($media) }}">
                <button class="cms-media-preview" type="button" data-cms-media-copy="{{ $mediaService->url($media) }}" aria-label="Copiar URL de {{ $media->original_filename }}">
                    @if ($media->media_type === 'image')
                        <img src="{{ $mediaService->url($media, 'thumbnail') }}" alt="{{ $media->alt_text ?: $media->original_filename }}">
                    @else
                        <span class="cms-media-file-icon">{{ strtoupper($media->extension) }}</span>
                    @endif
                </button>
                <div class="p-3">
                    <div class="fw-semibold text-truncate" title="{{ $media->original_filename }}">{{ $media->original_filename }}</div>
                    <div class="text-secondary small">{{ strtoupper($media->extension) }} · {{ $formatBytes($media->size) }}</div>
                    <div class="text-secondary small">{{ $media->width && $media->height ? $media->width.' × '.$media->height.' px' : 'Sem dimensões' }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-cms-media-copy="{{ $mediaService->url($media) }}">Copiar URL</button>
                        @if (! request()->boolean('deleted'))
                            <form method="POST" action="{{ route('admin.media.optimize', $media) }}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">Reoptimizar</button></form>
                            <form method="POST" action="{{ route('admin.media.destroy', $media) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.media.restore', $media->id) }}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">Restaurar</button></form>
                            <form method="POST" action="{{ route('admin.media.force-delete', $media->id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Eliminar definitivamente</button></form>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-4">{{ $mediaItems->links() }}</div>
@endsection
