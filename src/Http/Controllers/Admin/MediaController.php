<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\Events\MediaDeleted;
use Pcteckserv\CmsCore\Events\MediaRestored;
use Pcteckserv\CmsCore\Http\Requests\Admin\StoreMediaRequest;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateMediaRequest;
use Pcteckserv\CmsCore\Models\Media;
use Pcteckserv\CmsCore\Models\MediaCollection;
use Pcteckserv\CmsCore\Services\Media\MediaOptimizer;
use Pcteckserv\CmsCore\Services\Media\MediaService;

class MediaController extends Controller
{
    public function index(Request $request, MediaService $mediaService): View
    {
        Gate::authorize('media.view');

        $query = Media::query()
            ->with('collection')
            ->search($request->string('q')->toString())
            ->type($request->string('type')->toString() ?: null)
            ->when($request->filled('extension'), fn ($query) => $query->where('extension', $request->string('extension')))
            ->when($request->filled('collection_id'), fn ($query) => $query->where('collection_id', $request->integer('collection_id')))
            ->when($request->boolean('deleted'), fn ($query) => $query->onlyTrashed());

        $this->applySort($query, $request->string('sort')->toString());

        $media = $query->paginate(24)->withQueryString();
        $stats = $this->stats();

        return view('cms-core::admin.media.index', [
            'mediaItems' => $media,
            'collections' => MediaCollection::query()->orderBy('name')->get(),
            'stats' => $stats,
            'viewMode' => $request->cookie('cms_media_view', 'grid'),
            'mediaService' => $mediaService,
        ]);
    }

    public function store(StoreMediaRequest $request, MediaService $mediaService): JsonResponse
    {
        $items = [];
        $allowSvgUpload = $request->user()?->can('media.upload-svg') ?? false;

        foreach ($request->file('files', []) as $file) {
            $media = $mediaService->upload(
                $file,
                $request->user()?->getAuthIdentifier(),
                $request->integer('collection_id') ?: null,
                $allowSvgUpload,
            );
            $items[] = $this->payload($media, $mediaService);
        }

        return response()->json(['items' => $items], 201);
    }

    public function show(Media $media, MediaService $mediaService): JsonResponse
    {
        Gate::authorize('media.view');

        return response()->json($this->payload($media, $mediaService));
    }

    public function library(Request $request, MediaService $mediaService): JsonResponse
    {
        Gate::authorize('media.view');

        $media = Media::query()
            ->search($request->string('q')->toString())
            ->type($request->string('type')->toString() ?: 'image')
            ->latest()
            ->limit(48)
            ->get();

        return response()->json([
            'items' => $media->map(fn (Media $media): array => $this->payload($media, $mediaService))->values(),
        ]);
    }

    public function update(UpdateMediaRequest $request, Media $media): RedirectResponse
    {
        $media->update($request->validated());

        return back()->with('cms_media_success', 'Ficheiro actualizado com sucesso.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        Gate::authorize('media.delete');

        $media->delete();
        event(new MediaDeleted($media));

        return back()->with('cms_media_success', 'Ficheiro movido para eliminados.');
    }

    public function restore(int $media): RedirectResponse
    {
        Gate::authorize('media.restore');

        $item = Media::onlyTrashed()->findOrFail($media);
        $item->restore();
        event(new MediaRestored($item));

        return back()->with('cms_media_success', 'Ficheiro restaurado com sucesso.');
    }

    public function forceDelete(int $media, MediaService $mediaService): RedirectResponse
    {
        Gate::authorize('media.force-delete');

        $item = Media::onlyTrashed()->findOrFail($media);
        $mediaService->forceDelete($item);

        return redirect()->route('admin.media.index', ['deleted' => 1])->with('cms_media_success', 'Ficheiro eliminado definitivamente.');
    }

    public function optimize(Media $media, MediaOptimizer $optimizer): RedirectResponse
    {
        Gate::authorize('media.optimize');

        $optimizer->optimize($media);

        return back()->with('cms_media_success', 'Optimização executada.');
    }

    private function applySort($query, ?string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('original_filename'),
            'name_desc' => $query->orderByDesc('original_filename'),
            'size_desc' => $query->orderByDesc('size'),
            'size_asc' => $query->orderBy('size'),
            default => $query->latest(),
        };
    }

    private function stats(): array
    {
        return [
            'count' => Media::query()->count(),
            'size' => Media::query()->sum('size'),
            'saved' => Media::query()->whereNotNull('optimized_size')->get()->sum(fn (Media $media) => max(0, ($media->original_size ?? 0) - ($media->optimized_size ?? 0))),
            'pending' => Media::query()->where('optimization_status', '!=', Media::STATUS_OPTIMIZED)->where('media_type', 'image')->count(),
        ];
    }

    private function payload(Media $media, MediaService $mediaService): array
    {
        return [
            'id' => $media->id,
            'uuid' => $media->uuid,
            'url' => $media->media_type === 'image'
                ? $mediaService->url($media, 'optimized')
                : $mediaService->url($media),
            'original_url' => $mediaService->url($media),
            'thumbnail_url' => $mediaService->url($media, 'thumbnail'),
            'optimized_url' => $mediaService->url($media, 'optimized'),
            'alt_text' => $media->alt_text,
            'width' => $media->width,
            'height' => $media->height,
            'name' => $media->original_filename,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'optimization_status' => $media->optimization_status,
        ];
    }
}
