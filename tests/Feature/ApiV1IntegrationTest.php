<?php

namespace Tests\Feature;

use App\Domains\Shipments\States\PendingState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class ApiV1IntegrationTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_user_can_login_and_receive_contextual_sanctum_token(): void
    {
        $tenant = $this->tenant();
        $this->userForTenant($tenant, [
            'email' => 'admin@example.test',
            'password' => bcrypt('secret-password'),
            'role' => 'Company Admin',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.test',
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token', 'user' => ['id', 'tenant_id', 'email']]);
    }

    public function test_authenticated_api_can_create_shipments_for_the_current_tenant(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);

        Sanctum::actingAs($user, ['shipments:create']);

        $response = $this->postJson('/api/v1/shipments', [
            'merchant_id' => $merchant->id,
            'tracking_number' => 'CP-API-001',
            'destination_address' => 'API Customer Address',
            'cod_amount' => 75,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.tracking_number', 'CP-API-001');

        $this->assertDatabaseHas('shipments', [
            'tenant_id' => $tenant->id,
            'tracking_number' => 'CP-API-001',
            'state' => PendingState::$name,
            'cod_amount' => 75,
        ]);
    }
}
