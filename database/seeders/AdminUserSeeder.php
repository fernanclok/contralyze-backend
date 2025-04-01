<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear la empresa
        $company = Company::create([
            'name' => 'Contralyze',
            'email' => 'contralyze@gmail.com',
            'phone' => '1234567890',
            'address' => '123 Test Street',
            'city' => 'Tijuana',
            'state' => 'Baja California',
            'zip' => '22244',
            'size' => '1-10',
        ]);

        // Crear el departamento por defecto
        $department = Department::create([
            'name' => 'Admin',
            'description' => 'Admin department',
            'isActive' => true,
            'company_id' => $company->id,
        ]);

        // Crear el usuario administrador
        User::create([
            'first_name' => 'Fernando',
            'last_name' => 'Medina',
            'email' => 'fernando@gmail.com',
            'password' => Hash::make('fer1234'), 
            'role' => 'admin',
            'isActive' => true,
            'is_first_user' => true,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'created_by' => null,
        ]);
    }
}