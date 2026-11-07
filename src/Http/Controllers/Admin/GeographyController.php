<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Validation\ValidationException;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Geography\CountryCatalog;
use Pcteckserv\CmsCore\Geography\CountryEditor;
use Pcteckserv\CmsCore\Geography\ImportCountry;
use Pcteckserv\CmsCore\Http\Requests\EditCountryRequest;
use Pcteckserv\CmsCore\Http\Requests\ImportCountryRequest;

final class GeographyController
{
    public function index(CountryCatalog $catalog)
    {
        return view('cms-core::admin.geography.index', ['countries' => $catalog->countries(), 'imported' => $catalog->imported()]);
    }

    public function store(ImportCountryRequest $request, ImportCountry $importer, ActivityLoggerContract $logger)
    {
        try {
            $importer->persist($importer->prepare($request->validated('countries')));
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }
            return back()->withErrors($exception->errors())->withInput($request->safeInput());
        }
        $logger->log('core.geography.imported', category: 'geography', properties: ['countries' => $request->validated('countries')]);

        return redirect()->route('admin.geography.index')->with('cms_geography_success', 'As regiões foram importadas.');
    }

    public function edit(string $country, CountryCatalog $catalog)
    {
        return view('cms-core::admin.geography.edit', ['country' => $country, 'entry' => $catalog->imported()[$country] ?? abort(404)]);
    }

    public function update(EditCountryRequest $request, string $country, CountryEditor $editor, ActivityLoggerContract $logger)
    {
        try {
            $editor->save($country, $request->validated());
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }
            return back()->withErrors($exception->errors())->withInput($request->safeInput());
        }
        $logger->log('core.geography.updated', category: 'geography', properties: ['country' => $country]);

        return redirect()->route('admin.geography.edit', $country)->with('cms_geography_success', 'Os dados do país e das regiões foram guardados.');
    }
}
