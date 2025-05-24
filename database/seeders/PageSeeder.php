<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = DB::table('pages')->where(['page_type' => 1])->count();
        if(!$terms){
            DB::table('pages')->insert([
                [
                    'title' => 'Terms and Condition',
                    'description' => 'Terms and Condition',
                    'page_type' => 1,
                    'state_id' => 1,
                    'type_id' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => 1,
                ]
            ]);
        }
        $privacy = DB::table('pages')->where(['page_type' => 2])->count();
        if(!$privacy){
            DB::table('pages')->insert([
                [
                    'title' => 'Privacy Policy',
                    'description' => 'Privacy Policy',
                    'page_type' => 2,
                    'state_id' => 1,
                    'type_id' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => 1,
                ]
            ]);
        }
    }
}
