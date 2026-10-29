<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Pcteckserv\CmsCore\Http\Requests\Auth\TwoFactorRequest;
use Pcteckserv\CmsCore\Services\Auth\TwoFactorManager;

class TwoFactorSettingsController extends Controller
{
    public function begin(TwoFactorRequest $request, TwoFactorManager $manager): RedirectResponse
    {
        try {
            $factor = $manager->begin($request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }
        $request->session()->put(TwoFactorManager::SETUP, $factor->version);
        return redirect()->route('admin.profile.edit');
    }

    public function confirm(TwoFactorRequest $request, TwoFactorManager $manager): View|RedirectResponse
    {
        try {
            $codes = $manager->confirm($request->user(), (string) $request->session()->get(TwoFactorManager::SETUP, ''), $request->validated('two_factor_code'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $request->session()->forget(TwoFactorManager::SETUP);
        $request->session()->put(TwoFactorManager::VERIFIED, $manager->enabled($request->user())->version);
        $request->session()->regenerate();
        return view('cms-core::admin.profile.recovery-codes', ['codes' => $codes]);
    }

    public function disable(TwoFactorRequest $request, TwoFactorManager $manager): RedirectResponse
    {
        try {
            $manager->manage($request->user(), $request->validated('two_factor_code'), true);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }
        $request->session()->forget([TwoFactorManager::VERIFIED, TwoFactorManager::SETUP]);
        return redirect()->route('admin.profile.edit')->with('status', 'A autenticação de dois fatores foi desativada.');
    }

    public function regenerate(TwoFactorRequest $request, TwoFactorManager $manager): View|RedirectResponse
    {
        try {
            $codes = $manager->manage($request->user(), $request->validated('two_factor_code'), false);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }
        return view('cms-core::admin.profile.recovery-codes', ['codes' => $codes]);
    }
}
