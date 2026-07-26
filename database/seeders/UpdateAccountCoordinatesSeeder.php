<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class UpdateAccountCoordinatesSeeder extends Seeder
{
    /**
     * Real Kuwait area centers, spread across the country so markers land
     * on actual land/neighbourhoods instead of clustering in one spot or
     * drifting into the sea.
     */
    protected array $kuwaitAreas = [
        ['name' => 'Kuwait City',  'lat' => 29.3759, 'lng' => 47.9774],
        ['name' => 'Salmiya',     'lat' => 29.3345, 'lng' => 48.0764],
        ['name' => 'Hawally',     'lat' => 29.3328, 'lng' => 48.0281],
        ['name' => 'Jabriya',     'lat' => 29.3167, 'lng' => 48.0167],
        ['name' => 'Farwaniya',   'lat' => 29.2775, 'lng' => 47.9589],
        ['name' => 'Fahaheel',    'lat' => 29.0847, 'lng' => 48.1300],
        ['name' => 'Ahmadi',      'lat' => 29.0769, 'lng' => 48.0839],
        ['name' => 'Jahra',       'lat' => 29.3375, 'lng' => 47.6581],
        ['name' => 'Mangaf',      'lat' => 29.0958, 'lng' => 48.1264],
        ['name' => 'Khaitan',     'lat' => 29.2989, 'lng' => 47.9439],
        ['name' => 'Mishref',     'lat' => 29.2989, 'lng' => 48.0700],
        ['name' => 'Sabah Al Salem', 'lat' => 29.2367, 'lng' => 48.0664],
        ['name' => 'Fintas',      'lat' => 29.1489, 'lng' => 48.1258],
        ['name' => 'Riqqa',       'lat' => 29.2172, 'lng' => 48.1064],
        ['name' => 'Abu Halifa',  'lat' => 29.1244, 'lng' => 48.1197],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = Account::all();

        if ($accounts->isEmpty()) {
            $this->command->warn('مفيش accounts في الجدول أصلاً، شغّلي AccountSeeder الأول.');
            return;
        }

        foreach ($accounts as $account) {
            $area = $this->kuwaitAreas[array_rand($this->kuwaitAreas)];

            // jitter بسيط (~1-2 كم) عشان الحسابات في نفس المنطقة متبقاش
            // متركزة بالظبط على نفس النقطة فوق بعض
            $jitterLat = mt_rand(-200, 200) / 10000; // ±0.02 تقريبًا
            $jitterLng = mt_rand(-200, 200) / 10000;

            $account->update([
                'lat' => $area['lat'] + $jitterLat,
                'lng' => $area['lng'] + $jitterLng,
            ]);
        }

        $this->command->info("تم تحديث إحداثيات {$accounts->count()} حساب لتكون داخل الكويت.");
    }
}