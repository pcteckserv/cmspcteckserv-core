<?php

namespace Pcteckserv\CmsCore\Customers;

use Illuminate\Validation\ValidationException;

class CustomerDirectory
{
    /** Resolve a contact without authenticating it or changing an existing profile. */
    public function contact(string $name, string $email, ?string $phone = null): Customer
    {
        $email = mb_strtolower(trim($email));
        $customer = Customer::whereRaw('LOWER(email) = ?', [$email])->first() ?? Customer::firstOrCreate(['email' => $email], ['name' => $name, 'phone' => $phone, 'password' => bin2hex(random_bytes(32))]);
        if (! $customer->active) {
            throw ValidationException::withMessages(['email' => 'Contacte a empresa para reativar o seu registo.']);
        }

        return $customer;
    }
}
