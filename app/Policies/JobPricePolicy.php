<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\JobPrice;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobPricePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JobPrice');
    }

    public function view(AuthUser $authUser, JobPrice $jobPrice): bool
    {
        return $authUser->can('View:JobPrice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JobPrice');
    }

    public function update(AuthUser $authUser, JobPrice $jobPrice): bool
    {
        return $authUser->can('Update:JobPrice');
    }

    public function delete(AuthUser $authUser, JobPrice $jobPrice): bool
    {
        return $authUser->can('Delete:JobPrice');
    }

    public function restore(AuthUser $authUser, JobPrice $jobPrice): bool
    {
        return $authUser->can('Restore:JobPrice');
    }

    public function forceDelete(AuthUser $authUser, JobPrice $jobPrice): bool
    {
        return $authUser->can('ForceDelete:JobPrice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JobPrice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JobPrice');
    }

    public function replicate(AuthUser $authUser, JobPrice $jobPrice): bool
    {
        return $authUser->can('Replicate:JobPrice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JobPrice');
    }

}