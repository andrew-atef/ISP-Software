<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * Automatically assigns Spatie role based on UserRole enum.
     * This ensures UserRole and Spatie roles stay in sync.
     */
    public function created(User $user): void
    {
        $spatieRole = $this->getSpatieRole($user->role);

        if ($spatieRole) {
            try {
                if (!$user->hasRole($spatieRole)) {
                    $user->assignRole($spatieRole);
                }
            } catch (\Exception $e) {
                // Silently catch error if role doesn't exist yet
                // (e.g., during seeding before RolesAndPermissionsSeeder runs)
                // The role will be manually assigned by RolesAndPermissionsSeeder::syncUsersToRoles()
            }
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * If the user's role enum changes, automatically sync their Spatie role.
     * Uses wasChanged() since this fires AFTER the save (not isDirty).
     *
     * Example Scenario:
     * - User 'Islam' has role 'tech' with technician Spatie role
     * - Admin changes his role to 'dispatch' in Filament
     * - This observer detects the change and updates his Spatie role to 'dispatcher'
     * - Islam now immediately has dispatcher permissions
     */
    public function updated(User $user): void
    {
        // wasChanged() checks if the attribute was changed in this update cycle
        // (fires after save, so use wasChanged instead of isDirty)
        if (!$user->wasChanged('role')) {
            return;
        }

        $newSpatieRole = $this->getSpatieRole($user->role);

        if ($newSpatieRole) {
            try {
                // syncRoles() replaces all existing roles with the new one
                // Pass array format for safety and clarity
                $user->syncRoles([$newSpatieRole]);
            } catch (\Exception $e) {
                // Silently catch error if role doesn't exist
                // (shouldn't happen in normal operation after seeding)
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }

    /**
     * Map UserRole Enum to Spatie role name.
     *
     * This is a private helper method that centralizes the role mapping logic.
     * Mapping:
     * - UserRole::Admin -> 'super_admin'
     * - UserRole::Dispatch -> 'dispatcher'
     * - UserRole::Tech -> 'technician'
     */
    private function getSpatieRole(UserRole $role): ?string
    {
        return match ($role) {
            UserRole::Admin => 'super_admin',
            UserRole::Dispatch => 'dispatcher',
            UserRole::Tech => 'technician',
        };
    }
}
