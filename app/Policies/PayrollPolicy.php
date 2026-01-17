<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Payroll;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPolicy
{
    use HandlesAuthorization;

    /**
     * Admins: Full access
     * Technicians: View own payrolls only
     * Dispatchers: DENY
     */
    public function viewAny(AuthUser $authUser): bool
    {
        // Admins have full access via Shield permissions
        if ($authUser->can('ViewAny:Payroll')) {
            return true;
        }

        // Technicians can view the list (filtered to their own in Resource)
        return $authUser->role === UserRole::Tech;
    }

    /**
     * Admins: Can view any payroll
     * Technicians: Can only view their own payroll
     * Dispatchers: DENY
     */
    public function view(AuthUser $authUser, Payroll $payroll): bool
    {
        // Admins have full access via Shield permissions
        if ($authUser->can('View:Payroll')) {
            return true;
        }

        // Technicians can only view their own payroll
        return $authUser->role === UserRole::Tech && $payroll->user_id === $authUser->id;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Payroll');
    }

    public function update(AuthUser $authUser, Payroll $payroll): bool
    {
        return $authUser->can('Update:Payroll');
    }

    public function delete(AuthUser $authUser, Payroll $payroll): bool
    {
        return $authUser->can('Delete:Payroll');
    }

    public function restore(AuthUser $authUser, Payroll $payroll): bool
    {
        return $authUser->can('Restore:Payroll');
    }

    public function forceDelete(AuthUser $authUser, Payroll $payroll): bool
    {
        return $authUser->can('ForceDelete:Payroll');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Payroll');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Payroll');
    }

    public function replicate(AuthUser $authUser, Payroll $payroll): bool
    {
        return $authUser->can('Replicate:Payroll');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Payroll');
    }

}
