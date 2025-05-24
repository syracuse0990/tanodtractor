<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MultipleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $position = 0;
        for ($j = 0; $j <= 100; $j++) {
            DB::table('users')->insert([
                'name' => 'User ' . $position,
                'email' => "example.{$position}@example.com",
                'password' => Hash::make('password'),
                'phone' => '123456789',
                'role_id' => '2'
            ]);
            $position++;
        }
    }
}
