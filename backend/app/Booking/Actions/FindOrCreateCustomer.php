<?php

namespace App\Booking\Actions;

use App\Models\Customer;

class FindOrCreateCustomer
{
    /**
     * Customers are identified by phone number within a shop. Known details are only
     * filled in, never overwritten, so a typo in one booking can't rename a regular.
     */
    public function handle(string $phone, ?string $name = null, ?string $email = null, ?string $locale = null): Customer
    {
        $customer = Customer::withTrashed()->firstOrNew(['phone' => $phone]);

        if ($customer->trashed()) {
            $customer->restore();
        }

        $customer->name ??= $name;
        $customer->email ??= $email;
        if (! $customer->exists && $locale !== null) {
            $customer->locale = $locale;
        }

        $customer->save();

        return $customer;
    }
}
