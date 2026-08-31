<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateSiteOptionsRequest;
use Pcteckserv\CmsCore\Support\SiteOptions;

class SiteOptionsController extends Controller
{
    public function edit(SiteOptions $siteOptions): View
    {
        Gate::authorize('core.site-options.view');

        return view('cms-core::admin.site-options.edit', [
            'options' => $siteOptions->all(),
            'locales' => $this->locales(),
            'defaultSiteIconUrl' => $this->defaultSiteIconUrl(),
        ]);
    }

    public function update(UpdateSiteOptionsRequest $request, SiteOptions $siteOptions): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->boolean('remove_site_icon')) {
            $this->deleteStoredSiteIcon($validated['site_icon_url'] ?? null);
            $validated['site_icon_url'] = $this->defaultSiteIconUrl();
        } elseif ($request->hasFile('site_icon_file')) {
            $validated['site_icon_url'] = '/storage/'.$request->file('site_icon_file')->store('cms/site-icons', 'public');
        }

        unset($validated['site_icon_file'], $validated['remove_site_icon']);

        $siteOptions->setMany($validated);
        $this->syncAppUrlEnv($validated['site_url']);

        return back()->with('cms_site_options_success', 'Opções gerais guardadas com sucesso.');
    }

    private function locales(): array
    {
        return [
            'pt_PT' => 'Português',
            'en_US' => 'Inglês',
            'es_ES' => 'Espanhol',
            'fr_FR' => 'Francês',
        ];
    }

    private function deleteStoredSiteIcon(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! Str::startsWith($path, '/storage/cms/site-icons/')) {
            return;
        }

        Storage::disk('public')->delete(Str::after($path, '/storage/'));
    }

    private function defaultSiteIconUrl(): string
    {
        $default = config('cms-core.site_options.site_icon_url');

        return is_string($default) && $default !== ''
            ? $default
            : '/vendor/cms-core/images/favicon.png';
    }

    private function syncAppUrlEnv(string $url): void
    {
        config(['app.url' => $url]);

        $envPath = base_path('.env');
        $envValue = $this->formatEnvValue($url);

        if (! file_exists($envPath)) {
            file_put_contents($envPath, "APP_URL={$envValue}".PHP_EOL);

            return;
        }

        $contents = file_get_contents($envPath);

        if ($contents === false) {
            return;
        }

        if (preg_match('/^APP_URL=/m', $contents) === 1) {
            $contents = preg_replace('/^APP_URL=.*/m', "APP_URL={$envValue}", $contents);
        } else {
            $contents = rtrim($contents).PHP_EOL."APP_URL={$envValue}".PHP_EOL;
        }

        file_put_contents($envPath, $contents);
    }

    private function formatEnvValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|"/', $value) !== 1) {
            return $value;
        }

        return '"'.str_replace('"', '\"', $value).'"';
    }
}
