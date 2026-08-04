<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UpdateUserEmailSeeder extends Seeder
{
    /**
     * Pools used to generate real-sounding full names deterministically.
     * 40 x 40 = 1600 unique combinations, more than enough for any number
     * of seeded users. Re-running this seeder always produces the same
     * name for the same user (ordered by id), so it's idempotent.
     */
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
     * Tracks slugs already handed out during this run, so two users who
     * would otherwise land on the exact same base slug (e.g. name pool
     * wraps around past 1600 users) still get distinct email/user_name.
     */
    protected array $usedSlugs = [];

    /**
     * Deterministic real-name generator. Same $index -> same name every run.
     */
    protected function realName(int $index): string
    {
        $first = $this->firstNames[$index % count($this->firstNames)];
        $last  = $this->lastNames[intdiv($index, count($this->firstNames)) % count($this->lastNames)];

        return "{$first} {$last}";
    }

    /**
     * Turns "John Smith" into "john.smith", guaranteed unique across this
     * run (and re-runs stay stable because it's derived purely from the
     * deterministic name + a stable disambiguation counter).
     */
    protected function uniqueSlug(string $name): string
    {
        $base = Str::of($name)->lower()->replace(' ', '.')->__toString();
        $slug = $base;
        $n = 2;

        while (in_array($slug, $this->usedSlugs, true)) {
            $slug = "{$base}{$n}";
            $n++;
        }

        $this->usedSlugs[] = $slug;

        return $slug;
    }

    /**
     * Only touches `name`, `email`, and `user_name` - password, position,
     * branches, departments, manager hierarchy, etc. are left completely
     * untouched.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No users found - run UserSeeder first.');
            return;
        }

        foreach ($users as $index => $user) {
            $name = $this->realName($index);
            $slug = $this->uniqueSlug($name);

            $user->update([
                'name'      => $name,
             //   'email'     => "{$slug}@gmail.com",
                'user_name' => $slug,
            ]);
        }

        $this->command?->info("Updated names/emails/usernames for {$users->count()} user(s).");
    }
}