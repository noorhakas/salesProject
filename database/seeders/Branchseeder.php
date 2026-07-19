<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            ['name' => 'Cairo - Nasr City', 'address' => '12 Abbas El Akkad St, Nasr City, Cairo', 'phone' => '0221234567'],
            ['name' => 'Cairo - Maadi',     'address' => '45 Road 9, Maadi, Cairo',                 'phone' => '0223456789'],
            ['name' => 'Giza - Dokki',       'address' => '7 Tahrir St, Dokki, Giza',                 'phone' => '0233456781'],
            ['name' => 'Alexandria - Smouha','address' => '20 Fawzy Moaz St, Smouha, Alexandria',     'phone' => '0345678912'],
            ['name' => 'Mansoura - Downtown','address' => '3 El Gomhoria St, Mansoura, Dakahlia',     'phone' => '0501234567'],
        ];

        $departmentIds = Department::pluck('id');
        $userIds = User::pluck('id');

        foreach ($branches as $data) {
            $branch = Branch::firstOrCreate(
                ['name' => $data['name']],
                ['address' => $data['address'], 'phone' => $data['phone']]
            );

            // ربط أقسام عشوائية بكل فرع (لو فيه أقسام متسجلة)
            if ($departmentIds->isNotEmpty()) {
                $branch->departments()->syncWithoutDetaching(
                    $departmentIds->random(min(3, $departmentIds->count()))->toArray()
                );
            }

            // ربط مستخدمين عشوائيين بكل فرع (لو فيه مستخدمين متسجلين)
            if ($userIds->isNotEmpty()) {
                $branch->users()->syncWithoutDetaching(
                    $userIds->random(min(2, $userIds->count()))->toArray()
                );
            }
        }
    }
}