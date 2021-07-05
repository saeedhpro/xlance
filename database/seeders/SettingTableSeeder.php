<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Setting::all()->count() == 0) {
            Setting::create([
                'project_price' => 0,
                'distinguished_price' => 0,
                'sponsored_price' => 0
            ]);
        }
    }
}
