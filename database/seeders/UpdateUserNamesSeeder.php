<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUserNamesSeeder extends Seeder
{
   
    protected array $firstNames = [
        'James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph',
        'Thomas', 'Charles', 'Daniel', 'Matthew', 'Anthony', 'Mark', 'Paul', 'Steven',
        'Andrew', 'Kevin', 'Brian', 'George', 'Edward', 'Ronald', 'Timothy', 'Jason',
        'Jeffrey', 'Ryan', 'Jacob', 'Nicholas', 'Eric', 'Stephen', 'Jonathan', 'Larry',
        'Justin', 'Scott', 'Brandon', 'Benjamin', 'Samuel', 'Gregory', 'Alexander', 'Patrick',
    ];

    protected array $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas',
        'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White',
        'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young',
        'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores',
    ];

    /**
     * Deterministic real-name generator. Same $index -> same name every run.
     */
    protected function realName(int $index): string
    {
        $first = $this->firstNames[$index % count($this->firstNames)];
        $last  = $this->lastNames[intdiv($index, count($this->firstNames)) % count($this->lastNames)];

        return "{$first} {$last}";
    }

    
    public function run(): void
    {
        $users = User::orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No users found - run UserSeeder first.');
            return;
        }

        foreach ($users as $index => $user) {
            $user->update([
                'name' => $this->realName($index),
            ]);
        }

        $this->command?->info("Updated names for {$users->count()} user(s).");
    }
}