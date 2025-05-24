<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $systemAdmin = DB::table('users')->where(['role_id' => User::ROLE_SYSTEM_ADMIN])->count();
        if (!$systemAdmin) {
            DB::table('users')->insert([
                'name' => 'System Admin',
                'email' => 'systemadmin@tanodtractor.com',
                'phone' => '9874563210',
                'role_id' => User::ROLE_SYSTEM_ADMIN,
                'password' => Hash::make('sa@tanodtractor.2023'),
                'state_id' => User::STATE_ACTIVE
            ]);
        }
        $isAdminExist = DB::table('users')->where(['role_id' => User::ROLE_ADMIN])->count();
        if (!$isAdminExist) {
            //$adminRole = DB::table('roles')->where(['is_deleteable' => 0])->first();
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => 'leadsadmin@leadsagri.app',
                'phone' => '1234567890',
                'role_id' => User::ROLE_ADMIN,
                'password' => Hash::make('admin@123'),
                'state_id' => User::STATE_ACTIVE
            ]);
        }
        $isGovExists = DB::table('users')->where(['role_id'=>User::ROLE_GOVERNMENT])->count();
        if (!$isGovExists) {
            DB::table('users')->insert([
                'name' => 'Government',
                'email' => 'philmech@leadsagri.app',
                'phone' => '9632587410',
                'role_id' => User::ROLE_GOVERNMENT,
                'password' => Hash::make('gov@123'),
                'state_id' => User::STATE_ACTIVE
            ]);
        }
    }
}
