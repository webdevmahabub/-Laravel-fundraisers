<?php

namespace Tests;

use App\Models\User;
use HasinHayder\Tyro\Models\Role;

/**
 * Shared test utilities for the Fundraiser test suite.
 *
 * Role helpers: `attachRole()` on the Tyro User trait requires a Role *model*,
 * not a slug string. These helpers look up (or create) the correct Role row
 * after RefreshDatabase has wiped the seeds.
 */
trait TestHelpers
{
    /**
     * Attach a Tyro role to a user by slug, creating the role row if absent.
     */
    protected function giveRole(User $user, string $slug): void
    {
        $role = Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => ucfirst($slug)]
        );
        $user->attachRole($role);
    }

    /**
     * Create a user and attach the admin role to them.
     */
    protected function makeAdminUser(): User
    {
        $user = User::factory()->create();
        $this->giveRole($user, 'admin');

        return $user;
    }

    /**
     * Seed the six standard Tyro roles used by the application.
     * Called once per test class that needs role-based middleware.
     */
    protected function seedRoles(): void
    {
        $defaults = [
            ['slug' => 'admin',       'name' => 'Administrator'],
            ['slug' => 'user',        'name' => 'User'],
            ['slug' => 'super-admin', 'name' => 'Super Admin'],
            ['slug' => 'customer',    'name' => 'Customer'],
            ['slug' => 'editor',      'name' => 'Editor'],
            ['slug' => '*',           'name' => 'All'],
        ];

        foreach ($defaults as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], ['name' => $role['name']]);
        }
    }
}
