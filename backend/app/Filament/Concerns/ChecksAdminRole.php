<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait ChecksAdminRole
{
    protected static function currentAdmin(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function isFullAdmin(): bool
    {
        $user = static::currentAdmin();

        return $user !== null && $user->hasAnyRole(['super_admin', 'admin']);
    }

    public static function isSupportAgentOnly(): bool
    {
        $user = static::currentAdmin();

        return $user !== null
            && $user->hasRole('support_agent')
            && ! $user->hasAnyRole(['super_admin', 'admin']);
    }

    public static function canAccessPanelRoles(): bool
    {
        $user = static::currentAdmin();

        return $user !== null && $user->hasAnyRole(['super_admin', 'admin', 'support_agent']);
    }
}
