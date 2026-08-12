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

            $lastNumber = User::whereNotNull('emp_no')
                ->orderByDesc('emp_no')
                ->lockForUpdate()
                ->value('emp_no');

            $nextNumber = $lastNumber ? ((int) $lastNumber) + 1 : 1;

            User::whereNull('emp_no')
                ->orderBy('id')
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