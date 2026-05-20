<?php

namespace Database\Seeders;

use App\Domains\Billing\Models\Invoice;
use App\Domains\Billing\Models\LedgerTransaction;
use App\Domains\Fleet\Models\Driver;
use App\Domains\Fleet\Models\Route as DeliveryRoute;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Shipments\States\AssignedState;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use App\Domains\Shipments\States\InTransitState;
use App\Domains\Shipments\States\PackedState;
use App\Domains\Shipments\States\PendingState;
use App\Domains\Tenants\Models\Merchant;
use App\Domains\Tenants\Models\Tenant;
use App\Domains\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@cargopulse.test'],
            [
                'tenant_id' => null,
                'name' => 'CargoPulse Super Admin',
                'password' => Hash::make('password'),
                'role' => 'Super Admin',
            ]
        );
        $superAdmin->assignRole(Role::findByName('Super Admin', 'sanctum'));

        $this->seedTenant([
            'tenant' => ['name' => 'Cairo Express Logistics', 'domain' => 'cairo-express.test'],
            'users' => [
                ['name' => 'Mona Hassan', 'email' => 'admin@cairo-express.test', 'role' => 'Company Admin'],
                ['name' => 'Omar Nabil', 'email' => 'warehouse@cairo-express.test', 'role' => 'Warehouse Manager'],
                ['name' => 'Youssef Ali', 'email' => 'driver@cairo-express.test', 'role' => 'Driver'],
            ],
            'warehouses' => [
                ['name' => 'Nasr City Hub', 'code' => 'CAI-NC', 'location' => 'Nasr City, Cairo'],
                ['name' => '6th October Hub', 'code' => 'CAI-OCT', 'location' => '6th October City'],
            ],
            'merchants' => [
                ['name' => 'Nile Store', 'contact_email' => 'merchant@nile-store.test', 'contact_phone' => '+201000000001'],
                ['name' => 'Market Kart', 'contact_email' => 'merchant@market-kart.test', 'contact_phone' => '+201000000002'],
            ],
            'tracking_prefix' => 'CP-CAI',
        ]);

        $this->seedTenant([
            'tenant' => ['name' => 'Delta Freight Co', 'domain' => 'delta-freight.test'],
            'users' => [
                ['name' => 'Salma Adel', 'email' => 'admin@delta-freight.test', 'role' => 'Company Admin'],
                ['name' => 'Karim Fathy', 'email' => 'warehouse@delta-freight.test', 'role' => 'Warehouse Manager'],
                ['name' => 'Hany Samir', 'email' => 'driver@delta-freight.test', 'role' => 'Driver'],
            ],
            'warehouses' => [
                ['name' => 'Mansoura Hub', 'code' => 'DEL-MAN', 'location' => 'Mansoura'],
                ['name' => 'Alexandria Hub', 'code' => 'DEL-ALX', 'location' => 'Alexandria'],
            ],
            'merchants' => [
                ['name' => 'Delta Shop', 'contact_email' => 'merchant@delta-shop.test', 'contact_phone' => '+201000000003'],
            ],
            'tracking_prefix' => 'CP-DEL',
        ]);
    }

    private function seedTenant(array $data): void
    {
        $tenant = Tenant::updateOrCreate(
            ['domain' => $data['tenant']['domain']],
            ['name' => $data['tenant']['name'], 'status' => 'active']
        );

        $users = collect($data['users'])->mapWithKeys(function (array $userData) use ($tenant) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'role' => $userData['role'],
                ]
            );

            $user->assignRole(Role::findByName($userData['role'], 'sanctum'));

            return [$userData['role'] => $user];
        });

        $warehouses = collect($data['warehouses'])->map(function (array $warehouseData) use ($tenant) {
            return Warehouse::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $warehouseData['code']],
                [
                    'name' => $warehouseData['name'],
                    'location' => $warehouseData['location'],
                ]
            );
        })->values();

        $merchants = collect($data['merchants'])->map(function (array $merchantData) use ($tenant) {
            return Merchant::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'contact_email' => $merchantData['contact_email']],
                [
                    'name' => $merchantData['name'],
                    'contact_phone' => $merchantData['contact_phone'],
                    'webhook_url' => 'https://example.test/webhooks/cargopulse',
                ]
            );
        })->values();

        $driver = Driver::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $users['Driver']->id],
            [
                'license_number' => 'LIC-' . str_pad((string) $tenant->id, 4, '0', STR_PAD_LEFT),
                'vehicle_type' => 'van',
                'vehicle_plate' => 'CP-' . str_pad((string) $tenant->id, 3, '0', STR_PAD_LEFT),
                'status' => 'available',
            ]
        );

        $route = DeliveryRoute::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Morning Manifest', 'date' => now()->toDateString()],
            ['driver_id' => $driver->id, 'status' => 'in_progress']
        );

        $shipments = [
            ['suffix' => '001', 'state' => PendingState::$name, 'merchant' => 0, 'warehouse' => 0, 'fee' => null, 'cod' => 450, 'weight' => 3.2],
            ['suffix' => '002', 'state' => PackedState::$name, 'merchant' => 0, 'warehouse' => 0, 'fee' => 10, 'cod' => 0, 'weight' => 2.0],
            ['suffix' => '003', 'state' => AssignedState::$name, 'merchant' => 1 % $merchants->count(), 'warehouse' => null, 'fee' => 15, 'cod' => 125, 'weight' => 7.5, 'route_sequence' => 1],
            ['suffix' => '004', 'state' => InTransitState::$name, 'merchant' => 1 % $merchants->count(), 'warehouse' => null, 'fee' => 20, 'cod' => 0, 'weight' => 12.5, 'route_sequence' => 2],
            ['suffix' => '005', 'state' => DeliveredState::$name, 'merchant' => 0, 'warehouse' => null, 'fee' => 10, 'cod' => 300, 'weight' => 4.0, 'is_billed' => true],
            ['suffix' => '006', 'state' => FailedState::$name, 'merchant' => 0, 'warehouse' => 1, 'fee' => 10, 'cod' => 0, 'weight' => 1.0],
        ];

        foreach ($shipments as $shipmentData) {
            Shipment::withoutGlobalScopes()->updateOrCreate(
                ['tracking_number' => "{$data['tracking_prefix']}-{$shipmentData['suffix']}"],
                [
                    'tenant_id' => $tenant->id,
                    'merchant_id' => $merchants[$shipmentData['merchant']]->id,
                    'driver_id' => isset($shipmentData['route_sequence']) ? $driver->id : null,
                    'route_id' => isset($shipmentData['route_sequence']) ? $route->id : null,
                    'warehouse_id' => is_null($shipmentData['warehouse']) ? null : $warehouses[$shipmentData['warehouse']]->id,
                    'route_sequence' => $shipmentData['route_sequence'] ?? null,
                    'state' => $shipmentData['state'],
                    'priority' => $shipmentData['suffix'] === '001' ? 'high' : 'normal',
                    'pickup_address' => $warehouses[0]->location,
                    'delivery_address' => 'Demo customer address ' . $shipmentData['suffix'],
                    'pickup_lat' => 30.04440000,
                    'pickup_lng' => 31.23570000,
                    'delivery_lat' => 30.06260000,
                    'delivery_lng' => 31.24970000,
                    'weight' => $shipmentData['weight'],
                    'delivery_fee' => $shipmentData['fee'],
                    'cod_amount' => $shipmentData['cod'],
                    'is_billed' => $shipmentData['is_billed'] ?? false,
                    'created_at' => Carbon::now()->subDays((int) $shipmentData['suffix']),
                    'updated_at' => Carbon::now()->subDays((int) $shipmentData['suffix'])->addHours(4),
                ]
            );
        }

        $deliveredShipment = Shipment::withoutGlobalScopes()
            ->where('tracking_number', "{$data['tracking_prefix']}-005")
            ->first();

        if ($deliveredShipment) {
            $invoice = Invoice::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'invoice_number' => 'INV-DEMO-' . $tenant->id],
                [
                    'merchant_id' => $deliveredShipment->merchant_id,
                    'total_amount' => 10,
                    'cod_collected' => 300,
                    'status' => 'unpaid',
                    'period_start' => now()->startOfMonth(),
                    'period_end' => now()->endOfMonth(),
                    'pdf_url' => "https://example.test/invoices/INV-DEMO-{$tenant->id}.pdf",
                ]
            );

            $deliveredShipment->update(['invoice_id' => $invoice->id, 'is_billed' => true]);

            LedgerTransaction::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'shipment_id' => $deliveredShipment->id, 'type' => 'delivery_fee'],
                [
                    'merchant_id' => $deliveredShipment->merchant_id,
                    'invoice_id' => $invoice->id,
                    'amount' => -10,
                    'description' => "Delivery fee for shipment {$deliveredShipment->tracking_number}",
                ]
            );

            LedgerTransaction::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'shipment_id' => $deliveredShipment->id, 'type' => 'cod_collection'],
                [
                    'merchant_id' => $deliveredShipment->merchant_id,
                    'invoice_id' => $invoice->id,
                    'amount' => 300,
                    'description' => "COD collected for shipment {$deliveredShipment->tracking_number}",
                ]
            );
        }

        $locationTimestamp = Carbon::now()->startOfHour();

        DB::table('location_histories')->updateOrInsert(
            ['tenant_id' => $tenant->id, 'driver_id' => $driver->id, 'timestamp' => $locationTimestamp],
            [
                'lat' => 30.04440000,
                'lng' => 31.23570000,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
