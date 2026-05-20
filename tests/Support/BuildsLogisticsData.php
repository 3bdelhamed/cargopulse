<?php

namespace Tests\Support;

use App\Domains\Fleet\Models\Driver;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Shipments\States\ConfirmedState;
use App\Domains\Tenants\Models\Merchant;
use App\Domains\Tenants\Models\Tenant;
use App\Domains\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait BuildsLogisticsData
{
    protected function tenant(array $attributes = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Acme Logistics',
            'status' => 'active',
        ], $attributes));
    }

    protected function userForTenant(Tenant $tenant, array $attributes = []): User
    {
        return User::create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Admin',
            'email' => 'admin-' . $tenant->id . '@example.test',
            'password' => Hash::make('password'),
            'role' => 'Company Admin',
        ], $attributes));
    }

    protected function merchantForTenant(Tenant $tenant, array $attributes = []): Merchant
    {
        return Merchant::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Merchant',
            'contact_email' => 'merchant-' . $tenant->id . '@example.test',
        ], $attributes));
    }

    protected function driverForTenant(Tenant $tenant, User $user, array $attributes = []): Driver
    {
        return Driver::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'available',
        ], $attributes));
    }

    protected function warehouseForTenant(Tenant $tenant, array $attributes = []): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Main Hub',
            'code' => 'HUB-' . $tenant->id,
        ], $attributes));
    }

    protected function shipmentForTenant(Tenant $tenant, Merchant $merchant, array $attributes = []): Shipment
    {
        return Shipment::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
            'tracking_number' => 'TRK-' . fake()->unique()->numerify('######'),
            'state' => ConfirmedState::$name,
            'pickup_address' => 'Origin hub',
            'delivery_address' => 'Customer address',
        ], $attributes));
    }
}
