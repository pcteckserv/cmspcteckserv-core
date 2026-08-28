<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Consent;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Consent\ConsentManager;
use Pcteckserv\CmsCore\Models\ConsentRecord;

class ConsentRecordController extends Controller
{
    public function store(Request $request, ConsentManager $manager): JsonResponse
    {
        $settings = $manager->settings();

        if (! $settings->server_records_enabled) {
            return response()->json(['stored' => false]);
        }

        $validated = $request->validate([
            'anonymous_uuid' => ['nullable', 'uuid'],
            'version' => ['required', 'integer', 'min:1'],
            'categories' => ['required', 'array'],
            'categories.*' => ['boolean'],
        ]);

        ConsentRecord::query()->create([
            'anonymous_uuid' => $validated['anonymous_uuid'] ?? (string) Str::uuid(),
            'consent_version' => $validated['version'],
            'categories_json' => $validated['categories'],
        ]);

        return response()->json(['stored' => true]);
    }
}
