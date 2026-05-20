<?php

namespace Tests\Feature\E2E;

use App\Domains\Tenants\Actions\RegisterTenantAction;
use App\Domains\Tenants\DTOs\OnboardingData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingAndBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_onboarding_and_stripe_billing_flow()
    {
        config(['cashier.webhook.secret' => null]);

        // 1. Simulate RegisterTenantAction
        $action = new RegisterTenantAction();
        $data = new OnboardingData(
            company_name: 'Fast Logistics',
            admin_name: 'John Doe',
            admin_email: 'john@fastlogistics.com',
            password: 'password123'
        );
        $result = $action->execute($data);

        $tenant = $result['tenant'];
        $user = $result['user'];

        // Assign a dummy stripe_id to simulate previous customer creation
        $tenant->update(['stripe_id' => 'cus_test_123']);

        // 2. Simulate incoming Stripe webhook for subscription created
        $payload = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test_123',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'cancel_at_period_end' => false,
                    'current_period_start' => time(),
                    'current_period_end' => time() + 3600,
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_test_123',
                                'price' => [
                                    'id' => 'price_test_123',
                                    'product' => 'prod_test_123',
                                ],
                                'quantity' => 1,
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertSuccessful();

        // 3. Assert Tenant has an active subscription
        $this->assertTrue($tenant->fresh()->subscribed('default'));
        
        // Assert the user retained the 'Company Admin' role
        $this->assertTrue($user->fresh()->hasRole('Company Admin'));
    }
}
