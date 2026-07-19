<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Account;
use App\Models\Specialty;
use App\Models\AccType;
use App\Models\Classes;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accountIds    = Account::pluck('id');
        $specialtyIds  = Specialty::pluck('id');
        $accTypeIds    = AccType::pluck('id');

        // ملحوظة: الموديل Classes مش موجود عندي في الملفات اللي بعتهالي،
        // فلو الاسم مختلف أو الجدول فاضي هتحتاج تعدّل السطر ده.
        $classIds = class_exists(Classes::class) ? Classes::pluck('id') : collect();

        if ($accountIds->isEmpty() || $specialtyIds->isEmpty() || $accTypeIds->isEmpty()) {
            $this->command->warn('لازم تشغل AccountSeeder و SpecialtySeeder و AccTypeSeeder الأول قبل CustomerSeeder.');
            return;
        }

        $customerNames = [
            'Dr. Ahmed Mostafa',
            'Dr. Mona Kamal',
            'Dr. Youssef Adel',
            'Dr. Sara Nabil',
            'Dr. Karim Hassan',
            'Dr. Rania Fathy',
            'Dr. Tamer Salah',
            'Dr. Nourhan Samir',
        ];

        $possibleWorkDays = ['SAT', 'SUN', 'MON', 'TUES', 'WEND', 'THUR', 'FRI'];

        foreach ($customerNames as $name) {
            Customer::firstOrCreate(
                ['name' => $name],
                [
                    'account_id'       => $accountIds->random(),
                    'specialty_id'     => $specialtyIds->random(),
                    'phone'            => '010' . mt_rand(10000000, 99999999),
                    'phone1'           => null,
                    'acc_type_id'      => $accTypeIds->random(),
                    'image'            => null,
                    'brief'            => 'ملاحظات تجريبية عن العميل ' . $name,
                    'work_days'        => collect($possibleWorkDays)->random(mt_rand(3, 5))->values()->toArray(),
                    'work_start_time'  => '09:00',
                    'work_end_time'    => '17:00',
                    'class_id'         => $classIds->isNotEmpty() ? $classIds->random() : null,
                ]
            );
        }
    }
}