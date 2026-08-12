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
                ->orderByRaw('CAST(SUBSTRING(emp_no, 5) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->value('emp_no');

            $nextNumber = $lastNumber
                ? ((int) substr($lastNumber, 4)) + 1
                : 1;

            User::whereNull('emp_no')
                ->orderBy('id')
                ->lockForUpdate()
                ->chunkById(200, function ($users) use (&$nextNumber) {
                    foreach ($users as $user) {
                        $user->update([
                            'emp_no' => str_pad($nextNumber, 5, '0', STR_PAD_LEFT),
                        ]);

                        $nextNumber++;
                    }
                });
        });
    }
}