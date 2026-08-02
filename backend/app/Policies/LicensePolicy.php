<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\License;

class LicensePolicy
{
    public function viewAny(Customer $customer): bool
    {
        return true;
    }

    public function view(Customer $customer, License $license): bool
    {
        return $license->customer_id === $customer->id;
    }
}
