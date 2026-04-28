<?php

namespace Finn\PermissionEngine\Seeders;

use Finn\PermissionEngine\Models\Role;
use Finn\PermissionEngine\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = unserialize(env('ROLE_DATA', 'a:0:{}'));

        if (empty($roles)) {
            Role::factory()->create([
                'name' => 'Admin',
            ]);
        } else {
            foreach ($roles as $role) {
                Role::factory()->create($role);
            }
        }

        $permission = [
            '*'
        ];

        foreach ($permission as $perm) {
            Permission::factory()->create([
                'name' => $perm,
            ]);
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync(Permission::pluck('id')->toArray());
        }
    }
}