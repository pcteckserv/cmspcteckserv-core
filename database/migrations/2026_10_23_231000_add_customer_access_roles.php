<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Pcteckserv\CmsCore\Customers\Customer;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $definitions = [
            'core.client.access' => ['Aceder à própria área de cliente', 'Clientes'],
            'core.customers.view' => ['Consultar clientes', 'Clientes'],
            'core.customers.manage' => ['Gerir clientes, moradas e grupos', 'Clientes'],
        ];
        foreach ($definitions as $key => [$label, $group]) {
            Permission::firstOrCreate(['key' => $key], compact('label', 'group'));
        }
        $client = Role::firstOrCreate(['key' => 'core.client'], ['name' => 'Cliente', 'is_protected' => true]);
        $client->permissions()->syncWithoutDetaching(Permission::where('key', 'core.client.access')->pluck('id')->all());
        DB::table('cms_shop_customers')->select('id')->orderBy('id')->chunkById(500, function ($customers) use ($client) {
            DB::table('cms_role_user')->insertOrIgnore($customers->map(fn ($customer) => [
                'role_id' => $client->id, 'user_id' => $customer->id, 'user_type' => Customer::class,
            ])->all());
        });
        $manager = Role::firstOrCreate(['key' => 'ecommerce.manager'], ['name' => 'Gestor de loja', 'is_protected' => false]);
        $keys = ['core.customers.view', 'core.customers.manage', 'ecommerce.catalog.view', 'ecommerce.catalog.manage', 'ecommerce.orders.view', 'ecommerce.orders.manage', 'ecommerce.shipments.manage', 'ecommerce.shipping.manage', 'ecommerce.coupons.manage', 'ecommerce.reports.view', 'ecommerce.google.view'];
        foreach ($keys as $key) {
            Permission::firstOrCreate(['key' => $key], ['label' => $key, 'group' => str_starts_with($key, 'ecommerce.') ? 'Loja' : 'Clientes']);
        }
        $manager->permissions()->syncWithoutDetaching(Permission::whereIn('key', $keys)->pluck('id')->all());
        // Preserve existing staff access to customer administration.
        $old = Permission::where('key', 'ecommerce.customers.manage')->first();
        if ($old) {
            $new = Permission::whereIn('key', ['core.customers.view', 'core.customers.manage'])->pluck('id');
            foreach (DB::table('cms_role_permission')->where('permission_id', $old->id)->get() as $grant) {
                foreach ($new as $id) {
                    DB::table('cms_role_permission')->insertOrIgnore(['role_id' => $grant->role_id, 'permission_id' => $id]);
                }
            }
            foreach (DB::table('cms_permission_user')->where('permission_id', $old->id)->get() as $grant) {
                foreach ($new as $id) {
                    DB::table('cms_permission_user')->insertOrIgnore(['permission_id' => $id, 'user_id' => $grant->user_id, 'user_type' => $grant->user_type]);
                }
            }
        }
    }

    public function down(): void
    { /* Preserve assigned roles and permissions. */
    }
};
