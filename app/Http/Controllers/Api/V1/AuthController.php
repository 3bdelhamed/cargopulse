<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Tenants\Models\Merchant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'type' => 'nullable|in:user,merchant', // Identify if B2B Merchant or internal User
        ]);

        $type = $request->input('type', 'user');
        
        // 1. Authenticate External B2B Merchants
        if ($type === 'merchant') {
            $merchant = Merchant::where('contact_email', $request->email)->first();

            // Example merchant auth logic (assuming merchants have passwords in the future)
            // if (! $merchant || ! Hash::check($request->password, $merchant->password)) { ... }

            if (! $merchant) {
                throw ValidationException::withMessages(['email' => ['Merchant credentials do not match.']]);
            }

            $token = $merchant->createToken('merchant_api_token', ['shipments:create', 'shipments:read']);

            return response()->json([
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'merchant' => $merchant,
            ]);
        }

        // 2. Authenticate Internal SaaS Users
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Generate contextual Sanctum Token Abilities based on user role
        $abilities = match ($user->role) {
            'Company Admin' => ['*'], // Full access to tenant data
            'Warehouse Manager' => ['shipments:manage', 'warehouse:manage'],
            'Driver' => ['shipments:update', 'gps:update'],
            default => ['read-only'],
        };

        $token = $user->createToken('auth_token', $abilities);

        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
