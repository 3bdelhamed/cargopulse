<?php

namespace Tests\Feature;

use App\Domains\Tenants\Actions\RegisterTenantAction;
use App\Domains\Tenants\DTOs\OnboardingData;
use App\Domains\Tenants\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_tenant_scope_hides_other_tenants_records_and_auto_assigns_tenant_id(): void
    {
        $tenant = $this->tenant(['name' => 'Tenant A']);
        $otherTenant = $this->tenant(['name' => 'Tenant B']);
        $user = $this->userForTenant($tenant);

        $this->merchantForTenant($otherTenant, ['name' => 'Hidden Merchant']);

        $this->actingAs($user);

        $created = Merchant::create([
            'name' => 'Visible Merchant',
            'contact_email' => 'visible@example.test',
        ]);

        $this->assertSame($tenant->id, $created->tenant_id);
        $this->assertSame(['Visible Merchant'], Merchant::pluck('name')->all());
        $this->assertDatabaseHas('merchants', [
            'tenant_id' => $otherTenant->id,
            'name' => 'Hidden Merchant',
        ]);
    }

    public function test_register_tenant_onboards_company_admin_with_rbac_role(): void
    {
        $result = app(RegisterTenantAction::class)->execute(new OnboardingData(
            company_name: 'Fresh Logistics',
            admin_name: 'Fresh Admin',
            admin_email: 'fresh@example.test',
            password: 'secure-password',
        ));

        $this->assertSame('Fresh Logistics', $result['tenant']->name);
        $this->assertSame($result['tenant']->id, $result['user']->tenant_id);
        $this->assertTrue(Hash::check('secure-password', $result['user']->password));
        $this->assertTrue($result['user']->hasRole('Company Admin'));
    }
}
