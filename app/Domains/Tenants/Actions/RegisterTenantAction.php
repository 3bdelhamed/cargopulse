<?php

namespace App\Domains\Tenants\Actions;

use App\Domains\Tenants\DTOs\OnboardingData;
use App\Domains\Tenants\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterTenantAction
{
    public function execute(OnboardingData $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the Tenant (Company)
            $tenant = Tenant::create([
                'name' => $data->company_name,
                'status' => 'active',
            ]);

            // 2. Create the initial User (Company Admin)
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data->admin_name,
                'email' => $data->admin_email,
                'password' => Hash::make($data->password),
                'role' => 'Company Admin', // fallback column mapping
            ]);

            // 3. Assign the spatie/laravel-permission Role respecting multi-tenancy
            // If spatie/laravel-permission is configured for teams (tenant isolation),
            // we dynamically set the active team context before assigning the role.
            if (config('permission.teams')) {
                setPermissionsTeamId($tenant->id);
            }

            // Retrieve the foundational role seeded globally (or create it for the tenant)
            $role = Role::firstOrCreate([
                'name' => 'Company Admin', 
                'guard_name' => 'sanctum', // Ensure this matches API auth guards
            ]);
            
            $user->assignRole($role);

            return [
                'tenant' => $tenant,
                'user' => $user,
            ];
        });
    }
}
