<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\InventoryRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Admins: Full access
     * Dispatchers: View, Approve, Receive (Update)
     * Technicians: View own, Create own
     */
    public function viewAny(AuthUser $authUser): bool
    {
        // All roles can view inventory requests (filtered by scope in Resource)
        return $authUser->can('ViewAny:InventoryRequest');
    }

    /**
     * Admins: View any request
     * Dispatchers: View any request
     * Technicians: View only their own requests
     */
    public function view(AuthUser $authUser, InventoryRequest $inventoryRequest): bool
    {
        // Admins and Dispatchers can view any request
        if ($authUser->can('View:InventoryRequest')) {
            return true;
        }

        // Technicians can only view their own requests
        return $authUser->role === UserRole::Tech &&
               $inventoryRequest->requester_id === $authUser->id;
    }

    /**
     * All roles can create inventory requests
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InventoryRequest');
    }

    /**
     * Admins: Full update
     * Dispatchers: Update (for approval/receiving workflow)
     * Technicians: Cannot update
     */
    public function update(AuthUser $authUser, InventoryRequest $inventoryRequest): bool
    {
        // Technicians cannot update requests after submission
        if ($authUser->role === UserRole::Tech) {
            return false;
        }

        return $authUser->can('Update:InventoryRequest');
    }

    /**
     * Only Admins can delete requests
     */
    public function delete(AuthUser $authUser, InventoryRequest $inventoryRequest): bool
    {
        return $authUser->can('Delete:InventoryRequest');
    }

    public function restore(AuthUser $authUser, InventoryRequest $inventoryRequest): bool
    {
        return $authUser->can('Restore:InventoryRequest');
    }

    public function forceDelete(AuthUser $authUser, InventoryRequest $inventoryRequest): bool
    {
        return $authUser->can('ForceDelete:InventoryRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InventoryRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InventoryRequest');
    }

    public function replicate(AuthUser $authUser, InventoryRequest $inventoryRequest): bool
    {
        return $authUser->can('Replicate:InventoryRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InventoryRequest');
    }

}
