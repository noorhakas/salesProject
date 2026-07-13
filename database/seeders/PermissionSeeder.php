<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permissions')->delete();
        //
        $permissions = [
            ['name' => 'display Admins', 'guard_name' => 'web' , 'group_name'=>'Admins'],
            ['name' => 'create Admin', 'guard_name' => 'web' , 'group_name'=>'Admins'],
            ['name' => 'update Admin', 'guard_name' => 'web' , 'group_name'=>'Admins'],
            ['name' => 'delete Admin', 'guard_name' => 'web' , 'group_name'=>'Admins'],

            ['name' => 'display Users', 'guard_name' => 'web' , 'group_name'=>'MedicalRep.'],
            ['name' => 'create User', 'guard_name' => 'web' , 'group_name'=>'MedicalRep.'],
            ['name' => 'update User', 'guard_name' => 'web' , 'group_name'=>'MedicalRep.'],
            ['name' => 'delete User', 'guard_name' => 'web' , 'group_name'=>'MedicalRep.'],
             ['name' => 'view User', 'guard_name' => 'web' , 'group_name'=>'MedicalRep.'],

            ['name' => 'display Roles', 'guard_name' => 'web' , 'group_name'=>'Role'],
            ['name' => 'create Role', 'guard_name' => 'web' , 'group_name'=>'Role'],
            ['name' => 'update Role', 'guard_name' => 'web' , 'group_name'=>'Role'],
            ['name' => 'delete Role', 'guard_name' => 'web' , 'group_name'=>'Role'],

            ['name' => 'display Accounts', 'guard_name' => 'web' , 'group_name'=>'Accounts'],
            ['name' => 'create Account', 'guard_name' => 'web' , 'group_name'=>'Accounts'],
            ['name' => 'update Account', 'guard_name' => 'web' , 'group_name'=>'Accounts'],
            ['name' => 'delete Account', 'guard_name' => 'web' , 'group_name'=>'Accounts'],

            ['name' => 'display Doctors', 'guard_name' => 'web' , 'group_name'=>'Doctors'],
            ['name' => 'create Doctor', 'guard_name' => 'web' , 'group_name'=>'Doctors'],
            ['name' => 'update Doctor', 'guard_name' => 'web' , 'group_name'=>'Doctors'],
            ['name' => 'delete Doctor', 'guard_name' => 'web' , 'group_name'=>'Doctors'],


            ['name' => 'display Products', 'guard_name' => 'web' , 'group_name'=>'Products'],
            ['name' => 'create Product', 'guard_name' => 'web' , 'group_name'=>'Products'],
            ['name' => 'update Product', 'guard_name' => 'web' , 'group_name'=>'Products'],
            ['name' => 'delete Product', 'guard_name' => 'web' , 'group_name'=>'Products'],
            ['name' => 'view Product', 'guard_name' => 'web' , 'group_name'=>'Products'],

            ['name' => 'display Acc-Type', 'guard_name' => 'web' , 'group_name'=>'Acc-Type'],
            ['name' => 'create Acc-Type', 'guard_name' => 'web' , 'group_name'=>'Acc-Type'],
            ['name' => 'update Acc-Type', 'guard_name' => 'web' , 'group_name'=>'Acc-Type'],
            ['name' => 'delete Acc-Type', 'guard_name' => 'web' , 'group_name'=>'Acc-Type'],

            ['name' => 'display Brick', 'guard_name' => 'web' , 'group_name'=>'Bricks'],
            ['name' => 'create Brick', 'guard_name' => 'web' , 'group_name'=>'Bricks'],
            ['name' => 'update Brick', 'guard_name' => 'web' , 'group_name'=>'Bricks'],
            ['name' => 'delete Brick', 'guard_name' => 'web' , 'group_name'=>'Bricks'],

            ['name' => 'display Classes', 'guard_name' => 'web' , 'group_name'=>'Classes'],
            ['name' => 'create Class', 'guard_name' => 'web' , 'group_name'=>'Classes'],
            ['name' => 'update Class', 'guard_name' => 'web' , 'group_name'=>'Classes'],
            ['name' => 'delete Class', 'guard_name' => 'web' , 'group_name'=>'Classes'],

            ['name' => 'display Specialty', 'guard_name' => 'web' , 'group_name'=>'Specialty'],
            ['name' => 'create Specialty', 'guard_name' => 'web' , 'group_name'=>'Specialty'],
            ['name' => 'update Specialty', 'guard_name' => 'web' , 'group_name'=>'Specialty'],
            ['name' => 'delete Specialty', 'guard_name' => 'web' , 'group_name'=>'Specialty'],

            ['name' => 'display Company', 'guard_name' => 'web' , 'group_name'=>'Company'],
            ['name' => 'create Company', 'guard_name' => 'web' , 'group_name'=>'Company'],
            ['name' => 'update Company', 'guard_name' => 'web' , 'group_name'=>'Company'],
            ['name' => 'delete Company', 'guard_name' => 'web' , 'group_name'=>'Company'],
            // Add more permissions here as needed

            ['name' => 'display Category', 'guard_name' => 'web' , 'group_name'=>'Category'],
            ['name' => 'create Category', 'guard_name' => 'web' , 'group_name'=>'Category'],
            ['name' => 'update Category', 'guard_name' => 'web' , 'group_name'=>'Category'],
            ['name' => 'delete Category', 'guard_name' => 'web' , 'group_name'=>'Category'],
        
            ['name' => 'display Pharmacy', 'guard_name' => 'web' , 'group_name'=>'Pharmacy'],
            ['name' => 'create Pharmacy', 'guard_name' => 'web' , 'group_name'=>'Pharmacy'],
            ['name' => 'update Pharmacy', 'guard_name' => 'web' , 'group_name'=>'Pharmacy'],
            ['name' => 'delete Pharmacy', 'guard_name' => 'web' , 'group_name'=>'Pharmacy'],
        

            ['name' => 'display Pharmacy Group', 'guard_name' => 'web' , 'group_name'=>'Pharmacy Group'],
            ['name' => 'create Pharmacy Group', 'guard_name' => 'web' , 'group_name'=>'Pharmacy Group'],
            ['name' => 'update Pharmacy Group', 'guard_name' => 'web' , 'group_name'=>'Pharmacy Group'],
            ['name' => 'delete Pharmacy Group', 'guard_name' => 'web' , 'group_name'=>'Pharmacy Group'],


            ['name' => 'display Plans', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'approval Of Plans', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'display Current Visits', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'display Visits', 'guard_name' => 'web' , 'group_name'=>'Reports'],

            ['name' => 'display Notification', 'guard_name' => 'web' , 'group_name'=>'Others'],
            ['name' => 'display Logs', 'guard_name' => 'web' , 'group_name'=>'Others']

         
        ];

        // Insert permissions into the database
        DB::table('permissions')->insert($permissions);
    }
}
