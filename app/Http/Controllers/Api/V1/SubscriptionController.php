<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'price_id' => ['required', 'string', 'starts_with:price_'],
        ]);

        $tenant = auth()->user()->tenant;

        return $tenant->newSubscription('default', $request->price_id)
            ->checkout([
                'success_url' => route('dashboard', [], false),
                'cancel_url' => route('dashboard', [], false),
            ]);
    }
}
