<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\License;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class LicensePolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User || $actor instanceof Customer;
    }

    public function view(Authenticatable $actor, License $license): bool
    {
        if ($actor instanceof User) {
            return true;
        }

        return $actor instanceof Customer && $license->customer_id === $actor->id;
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User;
    }

    public function update(Authenticatable $actor, License $license): bool
    {
        return $actor instanceof User;
    }

    public function delete(Authenticatable $actor, License $license): bool
    {
        return $actor instanceof User;
    }

    public function deleteAny(Authenticatable $actor): bool
    {
        return $actor instanceof User;
    }
}
