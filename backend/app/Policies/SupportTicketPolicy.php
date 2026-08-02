<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\SupportTicket;

class SupportTicketPolicy
{
    public function viewAny(Customer $customer): bool
    {
        return true;
    }

    public function view(Customer $customer, SupportTicket $supportTicket): bool
    {
        return $supportTicket->customer_id === $customer->id;
    }

    public function create(Customer $customer): bool
    {
        return true;
    }

    public function reply(Customer $customer, SupportTicket $supportTicket): bool
    {
        return $supportTicket->customer_id === $customer->id;
    }
}
