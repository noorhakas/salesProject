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

            ['name' => 'display SalesRep', 'guard_name' => 'web' , 'group_name'=>'SalesRep.'],
            ['name' => 'create SalesRep', 'guard_name' => 'web' , 'group_name'=>'SalesRep.'],
            ['name' => 'update SalesRep', 'guard_name' => 'web' , 'group_name'=>'SalesRep.'],
            ['name' => 'delete SalesRep', 'guard_name' => 'web' , 'group_name'=>'SalesRep.'],
             ['name' => 'view SalesRep', 'guard_name' => 'web' , 'group_name'=>'SalesRep.'],

            ['name' => 'display Managers', 'guard_name' => 'web' , 'group_name'=>'Managers.'],
            ['name' => 'create Manager', 'guard_name' => 'web' , 'group_name'=>'Managers.'],
            ['name' => 'update Manager', 'guard_name' => 'web' , 'group_name'=>'Managers.'],
            ['name' => 'delete Manager', 'guard_name' => 'web' , 'group_name'=>'Managers.'],
            ['name' => 'view Manager', 'guard_name' => 'web' , 'group_name'=>'Managers.'],

            ['name' => 'display Roles', 'guard_name' => 'web' , 'group_name'=>'Role'],
            ['name' => 'create Role', 'guard_name' => 'web' , 'group_name'=>'Role'],
            ['name' => 'update Role', 'guard_name' => 'web' , 'group_name'=>'Role'],
            ['name' => 'delete Role', 'guard_name' => 'web' , 'group_name'=>'Role'],

            ['name' => 'display Accounts', 'guard_name' => 'web' , 'group_name'=>'Accounts'],
            ['name' => 'create Account', 'guard_name' => 'web' , 'group_name'=>'Accounts'],
            ['name' => 'update Account', 'guard_name' => 'web' , 'group_name'=>'Accounts'],
            ['name' => 'delete Account', 'guard_name' => 'web' , 'group_name'=>'Accounts'],

            ['name' => 'display Customers', 'guard_name' => 'web' , 'group_name'=>'Customers'],
            ['name' => 'create Customer', 'guard_name' => 'web' , 'group_name'=>'Customers'],
            ['name' => 'update Customer', 'guard_name' => 'web' , 'group_name'=>'Customers'],
            ['name' => 'delete Customer', 'guard_name' => 'web' , 'group_name'=>'Customers'],


            ['name' => 'display Products', 'guard_name' => 'web' , 'group_name'=>'Products'],
            ['name' => 'create Product', 'guard_name' => 'web' , 'group_name'=>'Products'],
            ['name' => 'update Product', 'guard_name' => 'web' , 'group_name'=>'Products'],
            ['name' => 'delete Product', 'guard_name' => 'web' , 'group_name'=>'Products'],
            ['name' => 'view Product', 'guard_name' => 'web' , 'group_name'=>'Products'],

            ['name' => 'display Company', 'guard_name' => 'web' , 'group_name'=>'Company'],
            ['name' => 'create Company', 'guard_name' => 'web' , 'group_name'=>'Company'],
            ['name' => 'update Company', 'guard_name' => 'web' , 'group_name'=>'Company'],
            ['name' => 'delete Company', 'guard_name' => 'web' , 'group_name'=>'Company'],
            ['name' => 'view Company', 'guard_name' => 'web' , 'group_name'=>'Company'],


            ['name' => 'display Category', 'guard_name' => 'web' , 'group_name'=>'Category'],
            ['name' => 'create Category', 'guard_name' => 'web' , 'group_name'=>'Category'],
            ['name' => 'update Category', 'guard_name' => 'web' , 'group_name'=>'Category'],
            ['name' => 'delete Category', 'guard_name' => 'web' , 'group_name'=>'Category'],
            ['name' => 'view Category', 'guard_name' => 'web' , 'group_name'=>'Category'],


            ['name' => 'display Plans', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'approval Of Plans', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'display Visits', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'display Overview Visits', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'display Visit Analytics', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'display Branches', 'guard_name' => 'web' , 'group_name'=>'Reports'],
            ['name' => 'display Map', 'guard_name' => 'web' , 'group_name'=>'Reports'],

             ['name' => 'display Sales', 'guard_name' => 'web' , 'group_name'=>'Sales'],
            ['name' => 'display Sales Chart', 'guard_name' => 'web' , 'group_name'=>'Sales'],

             ['name' => 'display Attendance', 'guard_name' => 'web' , 'group_name'=>'Attendance'],
            ['name' => 'display Public Holiday', 'guard_name' => 'web' , 'group_name'=>'Attendance'],


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

            ['name' => 'display Branch', 'guard_name' => 'web' , 'group_name'=>'Branch'],
            ['name' => 'create Branch', 'guard_name' => 'web' , 'group_name'=>'Branch'],
            ['name' => 'update Branch', 'guard_name' => 'web' , 'group_name'=>'Branch'],
            ['name' => 'delete Branch', 'guard_name' => 'web' , 'group_name'=>'Branch'],
            // Add more permissions here as needed

            ['name' => 'display Department', 'guard_name' => 'web' , 'group_name'=>'Department'],
            ['name' => 'create Department', 'guard_name' => 'web' , 'group_name'=>'Department'],
            ['name' => 'update Department', 'guard_name' => 'web' , 'group_name'=>'Department'],
            ['name' => 'delete Department', 'guard_name' => 'web' , 'group_name'=>'Department'],
        

           

            ['name' => 'display Notification', 'guard_name' => 'web' , 'group_name'=>'Setting'],
            ['name' => 'display Logs', 'guard_name' => 'web' , 'group_name'=>'Setting']

         
        ];

        // Insert permissions into the database
        DB::table('permissions')->insert($permissions);
    }
}
