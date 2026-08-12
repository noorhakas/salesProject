<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpNoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $lastNumber = User::orderByDesc('emp_no')
                ->lockForUpdate()
                ->value('emp_no');

            // Start from 1000
            $nextNumber = $lastNumber
                ? ((int) $lastNumber) + 1
                : 1000;

            User::orderBy('id')
                ->lockForUpdate()
                ->chunkById(200, function ($users) use (&$nextNumber) {

                    foreach ($users as $user) {
                        $user->update([
                            'emp_no' => $nextNumber,
                        ]);

                        $nextNumber++;
                    }
                });
        });
    }
}