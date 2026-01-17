<?php

namespace App\Policies;

use App\Enums\UserRole;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Admins: Full access
     * Dispatchers: View Only
     * Technicians: NO ACCESS
     */
    public function viewAny(AuthUser $authUser): bool
    {
        // Technicians cannot access User management
        if ($authUser->role === UserRole::Tech) {
            return false;
        }

        return $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser): bool
    {
        // Technicians cannot view User details
        if ($authUser->role === UserRole::Tech) {
            return false;
        }

        return $authUser->can('View:User');
    }

    /**
     * Only Admins can create users (Dispatchers cannot)
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    /**
     * Only Admins can update users (Dispatchers cannot)
     */
    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:User');
    }

    /**
     * Only Admins can delete users
     */
    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:User');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }

}
