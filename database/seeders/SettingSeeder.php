<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'app_name'         => 'Sales Project',
                'image'            => null,
                'map_key'          => '',
                'allow_distance'   => 100,
                'phone'            => '01000000000',
                'email'            => 'sales@gmail.com',
                'shift_time_from'  => '09:00:00',
                'shift_time_to'    => '17:00:00',
                'weekly_off_days'  => [1, 2],
                'android_build'    => [1,2,3,4,5,6,7,8,9,10,11,12],
                'enable_visit_check_distance'=>0,
                   
                'ios_build'        => [1,2,3,4,5,6,7,8,9,10,11,12]
            ]
        );
    }
}