<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DataSeeder extends Seeder
{
    public function run()
    {
        // Crear departamentos
        DB::table('departments')->insert([
            ['name' => 'Administración', 'description' => 'Departamento de administración general', 'company_id' => 1],
            ['name' => 'Tecnología', 'description' => 'Desarrollo y mantenimiento de sistemas', 'company_id' => 1],
            ['name' => 'Marketing', 'description' => 'Publicidad y estrategias de marca', 'company_id' => 1],
            ['name' => 'Recursos Humanos', 'description' => 'Gestión del talento y bienestar laboral', 'company_id' => 1],
            ['name' => 'Finanzas', 'description' => 'Administración y contabilidad', 'company_id' => 1],
        ]);

        // Crear clientes
        DB::table('clients')->insert([
            ['name' => 'Juan Pérez', 'email' => 'juan.perez@example.com', 'phone' => '5551112233', 'address' => 'Calle Falsa 123', 'isActive' => true, 'created_by' => 1],
            ['name' => 'María López', 'email' => 'maria.lopez@example.com', 'phone' => '5554445566', 'address' => 'Av. Principal 456', 'isActive' => true, 'created_by' => 1],
        ]);

        // Crear categorías presupuestarias
        DB::table('categories')->insert([
            ['name' => 'Gastos Administrativos', 'type' => 'expense', 'department_id' => 1, 'company_id' => 1],
            ['name' => 'Tecnología e Infraestructura', 'type' => 'investment', 'department_id' => 2, 'company_id' => 1],
            ['name' => 'Marketing y Publicidad', 'type' => 'expense', 'department_id' => 3, 'company_id' => 1],
            ['name' => 'Recursos Humanos', 'type' => 'expense', 'department_id' => 4, 'company_id' => 1],
            ['name' => 'Desarrollo de Negocios', 'type' => 'investment', 'department_id' => 5, 'company_id' => 1],
        ]);

        // Crear proveedores
        DB::table('suppliers')->insert([
            ['name' => 'Tech Solutions S.A.', 'email' => 'contacto@techsolutions.com', 'phone' => '5551234567', 'address' => 'Av. Tecnológica 123', 'isActive' => true, 'created_by' => 1],
            ['name' => 'Distribuidora Comercial Global', 'email' => 'ventas@distribuidoraglobal.com', 'phone' => '5559876543', 'address' => 'Calle Comercio 456', 'isActive' => true, 'created_by' => 1],
            ['name' => 'Servicios Financieros Integrales', 'email' => 'info@financierasintegrales.com', 'phone' => '5554443322', 'address' => 'Paseo de la Reforma 789', 'isActive' => true, 'created_by' => 1],
        ]);

        // Insertar solicitudes de presupuesto
        for ($i = 0; $i < 20; $i++) {
            DB::table('budget_requests')->insert([
                [
                    'user_id' => 1,
                    'category_id' => rand(1, 5),
                    'requested_amount' => rand(5000, 150000),
                    'description' => 'Solicitud de fondos para ' . ['compra de equipos', 'campaña digital', 'reparaciones en oficina', 'capacitación de personal', 'expansión de sucursales'][rand(0, 4)],
                    'request_date' => Carbon::now()->subMonths(rand(1, 6)),
                    'status' => ['approved', 'pending', 'rejected'][rand(0, 2)],
                    'reviewed_by' => 1,
                ],
            ]);
        }

        // Insertar transacciones financieras
        for ($i = 0; $i < 40; $i++) {
            DB::table('transactions')->insert([
                [
                    'type' => ['expense', 'income', 'transfer'][rand(0, 2)],
                    'amount' => rand(3000, 200000),
                    'description' => ['Pago a proveedores', 'Ingreso por venta de servicios', 'Compra de software', 'Renovación de licencias', 'Pago de nómina', 'Inversión en marketing'][rand(0, 5)],
                    'category_id' => rand(1, 5),
                    'user_id' => 1,
                    'supplier_id' => rand(1, 3),
                    'client_id' => rand(1, 2),
                    'transaction_date' => Carbon::now()->subMonths(rand(1, 6)),
                    'status' => ['completed', 'pending', 'cancelled'][rand(0, 2)],
                    'payment_method' => ['bank_transfer', 'credit_card', 'cash', 'paypal'][rand(0, 3)],
                    'reference_number' => 'TX' . rand(10000, 99999),
                    'deleted_at' => null,
                ],
            ]);
        }
    }
}
