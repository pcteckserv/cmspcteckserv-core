<?php

namespace Pcteckserv\CmsCore\Support;

use Illuminate\Http\Request;

class AccountFormChanges
{
    public static function apply(Request $request, ?string $email = null): void
    {
        if (! $request->boolean('account_form_controlled')) {
            return;
        }
        if (! $request->boolean('change_password')) {
            $request->merge(['password' => null, 'password_confirmation' => null]);
        }
        if ($email !== null && ! $request->boolean('change_email')) {
            $request->merge(['email' => $email]);
        }
    }
}
