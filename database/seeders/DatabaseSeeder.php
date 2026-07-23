<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
      //  $this->call(PermissionSeeder::class);
        // $this->call(AssignPermissionsToUserSeeder::class);
      //  $this->call(UserSeeder::class);
       


     /*   $this->call([
        Companyseeder::class,
        Categoryseeder::class,
        Departmentseeder::class,
        Branchseeder::class,
        Productseeder::class,
        UserSeeder::class
    ]);*/

   /* $this->call([
      //  ClassSeeder::class,
       // SpecialtySeeder::class,
        AcctypeSeeder::class,
        BricksSeeder::class,
        AccountSeeder::class,
        CustomerSeeder::class,
    ]);*/

   //  $this->call(PlanSeeder::class);
    // $this->call(VisitSeeder::class);
  //   $this->call(UserAccountSeeder::class);
  //   $this->call(UserProductSeeder::class);


      $this->call(PermissionSeeder::class);
       $this->call(AssignPermissionsToUserSeeder::class);
       
        
    }
}
