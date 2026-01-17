<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OriginalTech;
use Illuminate\Auth\Access\HandlesAuthorization;

class OriginalTechPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OriginalTech');
    }

    public function view(AuthUser $authUser, OriginalTech $originalTech): bool
    {
        return $authUser->can('View:OriginalTech');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OriginalTech');
    }

    public function update(AuthUser $authUser, OriginalTech $originalTech): bool
    {
        return $authUser->can('Update:OriginalTech');
    }

    public function delete(AuthUser $authUser, OriginalTech $originalTech): bool
    {
        return $authUser->can('Delete:OriginalTech');
    }

    public function restore(AuthUser $authUser, OriginalTech $originalTech): bool
    {
        return $authUser->can('Restore:OriginalTech');
    }

    public function forceDelete(AuthUser $authUser, OriginalTech $originalTech): bool
    {
        return $authUser->can('ForceDelete:OriginalTech');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OriginalTech');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OriginalTech');
    }

    public function replicate(AuthUser $authUser, OriginalTech $originalTech): bool
    {
        return $authUser->can('Replicate:OriginalTech');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OriginalTech');
    }

}