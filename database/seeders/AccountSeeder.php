<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Bricks;
use App\Models\AccType;
use App\Models\Classes;
use App\Models\PharmacyGroup;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brickIds  = Bricks::pluck('id');
        $typeIds   = AccType::pluck('id');

        // ملحوظة: الموديلين Classes و PharmacyGroup مش موجودين عندي في الملفات اللي بعتهالي،
        // فلو الاسم مختلف أو الجدول فاضي هتحتاج تعدّل الأسطر دي.
        $classIds         = class_exists(Classes::class) ? Classes::pluck('id') : collect();

        if ($brickIds->isEmpty() || $typeIds->isEmpty()) {
            $this->command->warn('لازم تشغل BricksSeeder و AccTypeSeeder الأول قبل AccountSeeder.');
            return;
        }

        $accounts = [
            ['name' => 'El Ezaby Pharmacy - Nasr City', 'phone' => '0221112223', 'address' => '10 Makram Ebeid, Nasr City, Cairo', 'lat' => 30.0626, 'lng' => 31.3459],
            ['name' => 'Seif Pharmacy - Maadi',          'phone' => '0223334445', 'address' => '5 Road 9, Maadi, Cairo',            'lat' => 29.9602, 'lng' => 31.2569],
            ['name' => 'Al Amal Clinic - Dokki',         'phone' => '0233335556', 'address' => '3 Tahrir St, Dokki, Giza',          'lat' => 30.0378, 'lng' => 31.2126],
            ['name' => 'Roshdy Pharmacy - Alexandria',   'phone' => '0344445667', 'address' => '15 Fouad St, Roshdy, Alexandria',    'lat' => 31.2156, 'lng' => 29.9553],
            ['name' => 'Dakahlia General Hospital',      'phone' => '0505556778', 'address' => 'El Gomhoria St, Mansoura',           'lat' => 31.0409, 'lng' => 31.3785],
            ['name' => 'Medico Distributor - Cairo',     'phone' => '0221119999', 'address' => 'Industrial Zone, Cairo',             'lat' => 30.0500, 'lng' => 31.2400],
        ];

        foreach ($accounts as $data) {
            Account::firstOrCreate(
                ['name' => $data['name']],
                [
                    'brick_id'          => $brickIds->random(),
                    'phone'             => $data['phone'],
                    'phone1'            => null,
                    'acc_type_id'       => $typeIds->random(),
                    'address'           => $data['address'],
                    'lat'               => $data['lat'],
                    'lng'               => $data['lng'],
                    'class_id'          => $classIds->isNotEmpty() ? $classIds->random() : null,
                    'pharmacy_group_id' => 0,
                ]
            );
        }
    }
}