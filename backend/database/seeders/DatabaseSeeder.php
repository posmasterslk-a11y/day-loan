<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            'Super Admin' => 'Full system access',
            'Branch Manager' => 'Manage branch customers and officers',
            'Collection Officer' => 'Field collections',
            'Cashier' => 'Manage daily branch cash',
        ];

        foreach ($roles as $name => $desc) {
            \App\Models\Role::firstOrCreate(['name' => $name], ['description' => $desc]);
        }

        $superAdminRole = \App\Models\Role::where('name', 'Super Admin')->first();

        \App\Models\User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
                'role_id' => $superAdminRole->id,
            ]
        );
    }
}
