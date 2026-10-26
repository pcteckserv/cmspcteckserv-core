<?php

namespace Pcteckserv\CmsCore\Customers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Plugins\PluginManager;
use Pcteckserv\CmsCore\Security\TwoFactor;
use Pcteckserv\CmsCore\Support\AccountFormChanges;
use Pcteckserv\CmsCore\Support\Website;

class CustomerController
{
    public function login()
    {
        return view('cms-core::customers.auth', ['mode' => 'login']);
    }

    public function register()
    {
        abort_unless(app(Website::class)->registration(), 404);

        return view('cms-core::customers.auth', ['mode' => 'register']);
    }

    public function forgot()
    {
        return view('cms-core::customers.auth', ['mode' => 'forgot']);
    }

    public function reset(Request $r, string $token)
    {
        return view('cms-core::customers.auth', ['mode' => 'reset', 'token' => $token, 'email' => $r->query('email')]);
    }

    public function authenticate(Request $r)
    {
        $d = $r->validate(['email' => 'required|email|max:254', 'password' => 'required|string|max:500']);
        if (! auth('shop_customer')->attempt(['email' => mb_strtolower(trim($d['email'])), 'password' => $d['password'], 'active' => true])) {
            return back()->withErrors(['email' => 'Não foi possível iniciar sessão com estes dados.']);
        }
        $r->session()->regenerate();
        $target = $r->session()->pull('cms_shop.return_checkout', false) && Route::has('shop.checkout') && app(PluginManager::class)->isEnabled('ecommerce') ? 'shop.checkout' : 'customers.account';
        if ($challenge = app(TwoFactor::class)->challengeIfNeeded($r, 'shop_customer', route($target))) {
            return $challenge;
        }

        return redirect()->route($target);
    }

    public function store(Request $r)
    {
        abort_unless(app(Website::class)->registration(), 403);
        $r->merge(['email' => mb_strtolower(trim((string) $r->input('email')))]);
        $d = $r->validate(['name' => 'required|string|max:190', 'email' => 'required|email|max:254|unique:cms_shop_customers,email', 'password' => 'required|string|min:12|max:200|confirmed', 'terms' => 'accepted']);
        $c = Customer::create(array_intersect_key($d, array_flip(['name', 'email', 'password'])));
        auth('shop_customer')->login($c);
        $r->session()->regenerate();

        return redirect()->route('customers.account');
    }

    public function logout(Request $r)
    {
        auth('shop_customer')->logout();
        $r->session()->forget(['two_factor.shop_customer', 'shop_customer.identity']);
        $r->session()->regenerate();
        $r->session()->regenerateToken();

        return redirect()->route('customers.login');
    }

    public function account()
    {
        $customer = auth('shop_customer')->user();

        return response()->view('cms-core::customers.account', ['customer' => $customer, 'sections' => app(CustomerModules::class)->sections($customer, false)])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $r)
    {
        $c = auth('shop_customer')->user();
        AccountFormChanges::apply($r);
        $d = $r->validate(['name' => 'required|string|max:190', 'company' => 'nullable|string|max:190', 'phone' => 'nullable|string|max:30', 'vat' => 'nullable|string|max:30', 'current_password' => 'required|string|max:500', 'password' => 'nullable|string|min:12|max:200|confirmed']);
        if (! Hash::check($d['current_password'], $c->password)) {
            return back()->withErrors(['current_password' => 'Palavra-passe incorreta.']);
        }
        unset($d['current_password']);
        if (empty($d['password'])) {
            unset($d['password']);
        } else {
            $d['remember_token'] = null;
        }
        $c->update($d);
        $r->session()->put('shop_customer.identity', ['id' => $c->id, 'hash' => $c->getAuthPassword()]);
        $r->session()->regenerate();

        return back()->with('status', 'Perfil atualizado.');
    }

    public function profile()
    {
        return response()->view('cms-core::customers.profile', ['customer' => auth('shop_customer')->user()])->header('Cache-Control', 'private, no-store');
    }

    public function sendReset(Request $r)
    {
        $d = $r->validate(['email' => 'required|email|max:254']);
        Password::broker('shop_customers')->sendResetLink(['email' => mb_strtolower(trim($d['email'])), 'active' => true]);

        return back()->with('status', 'Se existir uma conta ativa, receberá as instruções de recuperação.');
    }

    public function addresses()
    {
        return response()->view('cms-core::customers.addresses', ['addresses' => auth('shop_customer')->user()->addresses()->get()])->header('Cache-Control', 'private, no-store');
    }

    public function saveAddress(Request $r, ?int $address = null)
    {
        $c = auth('shop_customer')->user();
        $d = $r->validate(['label' => 'required|string|max:80'] + AddressFields::rules());
        $model = $address ? $c->addresses()->findOrFail($address) : $c->addresses()->make();
        abort_if(! $address && $c->addresses()->count() >= 20, 422);
        $model->fill(['label' => $d['label'], 'details' => array_diff_key($d, ['label' => true])])->save();

        return back()->with('status', 'Morada guardada.');
    }

    public function deleteAddress(int $address)
    {
        auth('shop_customer')->user()->addresses()->findOrFail($address)->delete();

        return back()->with('status', 'Morada removida.');
    }

    public function resetPassword(Request $r)
    {
        $d = $r->validate(['token' => 'required|string', 'email' => 'required|email', 'password' => 'required|string|min:12|max:200|confirmed']);
        $d['email'] = mb_strtolower(trim($d['email']));
        $status = Password::broker('shop_customers')->reset($d, function ($c, $password) {
            $c->update(['password' => $password, 'remember_token' => null]);
        });

        return $status === Password::PASSWORD_RESET ? redirect()->route('customers.login')->with('status', 'Palavra-passe atualizada.') : back()->withErrors(['email' => 'A ligação expirou ou não é válida.']);
    }
}
