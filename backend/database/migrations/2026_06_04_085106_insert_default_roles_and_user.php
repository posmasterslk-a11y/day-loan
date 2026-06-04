<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert Default Roles
        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'Super Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('roles')->insert([
            ['name' => 'Manager', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Collection Officer', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Insert Default Admin User
        DB::table('users')->insert([
            'name' => 'System Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRoleId,
            'branch_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('email', 'admin@gmail.com')->delete();
        DB::table('roles')->whereIn('name', ['Super Admin', 'Manager', 'Collection Officer'])->delete();
    }
};
