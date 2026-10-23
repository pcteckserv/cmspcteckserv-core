<?php

namespace Pcteckserv\CmsCore\Customers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Pcteckserv\CmsCore\Security\AccountFactor;

class AdminCustomersController
{
    public function index(Request $r)
    {
        $d = $r->validate(['q' => 'nullable|string|max:150', 'group' => 'nullable|integer', 'active' => 'nullable|in:0,1']);
        $records = Customer::with('groups')->when($d['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$s.'%')->orWhere('email', 'like', '%'.$s.'%')->orWhere('vat', 'like', '%'.$s.'%')))->when($d['group'] ?? null, fn ($q, $g) => $q->whereHas('groups', fn ($q) => $q->where('cms_customer_groups.id', $g)))->when(isset($d['active']), fn ($q) => $q->where('active', $d['active']))->orderBy('name')->paginate(20)->withQueryString();

        return view('cms-core::customers.index', ['records' => $records, 'groups' => Group::orderBy('name')->get()]);
    }

    public function edit(?int $customer = null)
    {
        $c = $customer ? Customer::with(['groups', 'addresses'])->findOrFail($customer) : new Customer;

        return view('cms-core::customers.edit', ['customer' => $c, 'groups' => Group::orderBy('name')->get(), 'sections' => $c->exists ? app(CustomerModules::class)->sections($c) : []]);
    }

    public function save(Request $r, ?int $customer = null)
    {
        $r->merge(['email' => mb_strtolower(trim((string) $r->input('email')))]);
        $d = $r->validate(['name' => 'required|string|max:190', 'email' => ['required', 'email', 'max:254', Rule::unique('cms_shop_customers')->ignore($customer)], 'company' => 'nullable|string|max:190', 'phone' => 'nullable|string|max:30', 'vat' => 'nullable|string|max:30', 'active' => 'required|boolean', 'groups' => 'nullable|array|max:100', 'groups.*' => 'integer|distinct|exists:cms_customer_groups,id']);
        $c = DB::transaction(function () use ($d, $customer) {
            $c = $customer ? Customer::lockForUpdate()->findOrFail($customer) : new Customer(['password' => bin2hex(random_bytes(32))]);
            $c->fill(array_diff_key($d, ['groups' => true]));
            if (! $c->active) {
                $c->remember_token = null;
            }
            $c->save();
            $c->groups()->sync($d['groups'] ?? []);

            return $c;
        });

        return redirect()->route('admin.customers.edit', $c->id)->with('status', 'Cliente guardado. Uma conta nova pode definir a palavra-passe pela recuperação de acesso.');
    }

    public function destroy(int $customer)
    {
        DB::transaction(function () use ($customer) {
            $c = Customer::lockForUpdate()->findOrFail($customer);
            if ($c->hasHistory()) {
                $c->update(['active' => false, 'remember_token' => null]);
            } else {
                AccountFactor::where('guard', 'shop_customer')->where('account_id', $c->id)->delete();
                $c->delete();
            }
        });

        return redirect()->route('admin.customers.index')->with('status', 'Cliente eliminado ou inativado quando existe histórico.');
    }

    public function address(Request $r, int $customer, ?int $address = null)
    {
        $c = Customer::findOrFail($customer);
        $d = $r->validate(['label' => 'required|string|max:80'] + AddressFields::rules());
        $a = $address ? $c->addresses()->findOrFail($address) : $c->addresses()->make();
        $a->fill(['label' => $d['label'], 'details' => array_diff_key($d, ['label' => true])])->save();

        return back()->with('status', 'Morada guardada. Os documentos anteriores mantêm os dados originais.');
    }

    public function deleteAddress(int $customer, int $address)
    {
        Customer::findOrFail($customer)->addresses()->findOrFail($address)->delete();

        return back()->with('status', 'Morada removida.');
    }

    public function groups()
    {
        return view('cms-core::customers.groups', ['groups' => Group::withCount('customers')->orderBy('name')->get()]);
    }

    public function saveGroup(Request $r, ?int $group = null)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:100', Rule::unique('cms_customer_groups')->ignore($group)], 'description' => 'nullable|string|max:2000']);
        $m = $group ? Group::findOrFail($group) : new Group;
        $m->fill($d)->save();

        return back()->with('status', 'Grupo guardado.');
    }

    public function deleteGroup(int $group)
    {
        $g = Group::findOrFail($group);
        if ($g->customers()->exists()) {
            throw ValidationException::withMessages(['group' => 'Retire os clientes do grupo antes de o eliminar.']);
        }
        $g->delete();

        return back()->with('status', 'Grupo eliminado.');
    }
}
