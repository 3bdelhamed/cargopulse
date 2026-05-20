<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Core Logistics Permissions
        $permissions = [
            'create-shipments',
            'view-shipments',
            'update-shipment-status',
            'assign-routes',
            'view-routes',
            'update-gps-coordinates',
            'manage-warehouse',
            'view-billing',
            'manage-users'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }
        
        // Seed Foundational System Roles
        // Note: For multi-tenancy with strict RBAC, Spatie teams (tenant_id) ensures
        // users only possess these permissions within their isolated company database boundary.

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'sanctum']);
        $superAdmin->givePermissionTo(Permission::all());

        $companyAdmin = Role::firstOrCreate(['name' => 'Company Admin', 'guard_name' => 'sanctum']);
        $companyAdmin->givePermissionTo($permissions); // Granted full tenant-level permissions

        $warehouseManager = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'sanctum']);
        $warehouseManager->givePermissionTo([
            'create-shipments',
            'view-shipments',
            'update-shipment-status',
            'manage-warehouse'
        ]);

        $driver = Role::firstOrCreate(['name' => 'Driver', 'guard_name' => 'sanctum']);
        $driver->givePermissionTo([
            'view-routes',
            'update-shipment-status',
            'update-gps-coordinates'
        ]);
    }
}
