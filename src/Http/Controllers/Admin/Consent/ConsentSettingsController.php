<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin\Consent;

use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract;
use Pcteckserv\CmsCore\Http\Requests\Admin\Consent\UpdateConsentSettingsRequest;

class ConsentSettingsController extends Controller
{
    public function edit(ConsentManagerContract $manager)
    {
        abort_unless(auth()->user()?->can('consent.manage'), 403);

        return view('cms-core::admin.consent.settings', ['settings' => $manager->settings(), 'defaultTexts' => $manager->defaultTexts()]);
    }

    public function update(UpdateConsentSettingsRequest $request, ConsentManagerContract $manager)
    {
        $settings = $manager->settings();
        $settings->update([
            'banner_enabled' => $request->boolean('banner_enabled'),
            'server_records_enabled' => $request->boolean('server_records_enabled'),
            'texts' => array_replace($manager->defaultTexts(), $request->validated('texts')),
        ]);
        $manager->forgetCache();

        return redirect()->route('admin.consent.settings.edit')->with('status', 'Configuração guardada.');
    }

    public function publish(ConsentManagerContract $manager)
    {
        abort_unless(auth()->user()?->can('consent.publish'), 403);
        $manager->publish(request()->boolean('increment_version'));

        return redirect()->route('admin.consent.dashboard')->with('status', 'Configuração publicada.');
    }
}
