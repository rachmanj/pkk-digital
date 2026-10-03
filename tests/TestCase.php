<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function actingAdmin(): User
    {
        $this->seedRoles();

        return User::query()->where('email', 'admin@pkk.test')->firstOrFail();
    }

    protected function userForRole(string $role, array $attributes = []): User
    {
        $this->seedRoles();

        $user = User::factory()->create(array_merge([
            'password' => 'password',
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }
}
