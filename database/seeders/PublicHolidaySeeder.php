<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PublicHoliday;

class PublicHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            [
                'name'      => 'New Year',
                'date_from' => '2026-01-01',
                'date_to'   => '2026-01-01',
                'active'    => 1,
            ],
            [
                'name'      => 'Eid Al-Fitr',
                'date_from' => '2026-03-20',
                'date_to'   => '2026-03-22',
                'active'    => 1,
            ],
            [
                'name'      => 'Eid Al-Adha',
                'date_from' => '2026-05-27',
                'date_to'   => '2026-05-30',
                'active'    => 1,
            ],
            [
                'name'      => 'Labour Day',
                'date_from' => '2026-05-01',
                'date_to'   => '2026-05-01',
                'active'    => 1,
            ],
            [
                'name'      => 'Islamic New Year',
                'date_from' => '2026-06-16',
                'date_to'   => '2026-06-16',
                'active'    => 1,
            ],
            [
                'name'      => 'Revolution Day',
                'date_from' => '2026-07-23',
                'date_to'   => '2026-07-23',
                'active'    => 1,
            ],
            [
                'name'      => "Prophet's Birthday",
                'date_from' => '2026-08-26',
                'date_to'   => '2026-08-26',
                'active'    => 1,
            ],
            [
                'name'      => 'Armed Forces Day',
                'date_from' => '2026-10-06',
                'date_to'   => '2026-10-06',
                'active'    => 1,
            ],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                ['name' => $holiday['name']],
                $holiday
            );
        }
    }
}