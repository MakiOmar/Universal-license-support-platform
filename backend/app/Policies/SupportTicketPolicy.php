<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class SupportTicketPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User || $actor instanceof Customer;
    }

    public function view(Authenticatable $actor, SupportTicket $supportTicket): bool
    {
        if ($actor instanceof User) {
            return true;
        }

        return $actor instanceof Customer && $supportTicket->customer_id === $actor->id;
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User || $actor instanceof Customer;
    }

    public function update(Authenticatable $actor, SupportTicket $supportTicket): bool
    {
        return $actor instanceof User;
    }

    public function delete(Authenticatable $actor, SupportTicket $supportTicket): bool
    {
        return $actor instanceof User;
    }

    public function deleteAny(Authenticatable $actor): bool
    {
        return $actor instanceof User;
    }

    public function reply(Authenticatable $actor, SupportTicket $supportTicket): bool
    {
        if ($actor instanceof User) {
            return true;
        }

        return $actor instanceof Customer && $supportTicket->customer_id === $actor->id;
    }
}
