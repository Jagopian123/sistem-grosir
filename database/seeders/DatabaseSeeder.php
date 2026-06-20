<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $owner = User::firstOrCreate(
            ['email' => 'admin@sistemgrosir.test'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );

        if (! $owner->hasRole(Role::Owner->value)) {
            $owner->assignRole(Role::Owner->value);
        }

        $this->call(MasterDataSeeder::class);
    }
}
