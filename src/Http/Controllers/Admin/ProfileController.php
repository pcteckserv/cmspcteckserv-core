<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateProfileRequest;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('cms-core::admin.profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request, ActivityLoggerContract $activityLogger): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $oldValues = $user->only(['name', 'email']);
        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $passwordChanged = ! empty($data['password']);

        if ($passwordChanged) {
            $user->password = Hash::make($data['password']);
            $user->setRememberToken(Str::random(60));
        }

        DB::transaction(function () use ($user, $activityLogger, $oldValues, $passwordChanged): void {
            $user->save();

            $activityLogger->log(
                action: 'user.profile_updated',
                category: 'users',
                description: 'Utilizador atualizou os seus dados.',
                subject: $user,
                oldValues: $oldValues,
                newValues: $user->only(['name', 'email']),
                properties: ['password_changed' => $passwordChanged],
            );
        });

        if ($passwordChanged) {
            $request->session()->regenerate();
        }

        return redirect()->route('admin.profile.edit')->with('status', 'Os seus dados foram atualizados com sucesso.');
    }
}
