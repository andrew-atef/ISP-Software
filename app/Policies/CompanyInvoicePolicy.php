<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CompanyInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyInvoicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompanyInvoice');
    }

    public function view(AuthUser $authUser, CompanyInvoice $companyInvoice): bool
    {
        return $authUser->can('View:CompanyInvoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompanyInvoice');
    }

    public function update(AuthUser $authUser, CompanyInvoice $companyInvoice): bool
    {
        return $authUser->can('Update:CompanyInvoice');
    }

    public function delete(AuthUser $authUser, CompanyInvoice $companyInvoice): bool
    {
        return $authUser->can('Delete:CompanyInvoice');
    }

    public function restore(AuthUser $authUser, CompanyInvoice $companyInvoice): bool
    {
        return $authUser->can('Restore:CompanyInvoice');
    }

    public function forceDelete(AuthUser $authUser, CompanyInvoice $companyInvoice): bool
    {
        return $authUser->can('ForceDelete:CompanyInvoice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CompanyInvoice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CompanyInvoice');
    }

    public function replicate(AuthUser $authUser, CompanyInvoice $companyInvoice): bool
    {
        return $authUser->can('Replicate:CompanyInvoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CompanyInvoice');
    }

}