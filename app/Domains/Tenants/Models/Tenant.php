<?php

namespace App\Domains\Tenants\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable;

    protected $guarded = ['id'];
}
