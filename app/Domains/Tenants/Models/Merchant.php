<?php

namespace App\Domains\Tenants\Models;

use App\Domains\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Merchant extends Model
{
    use HasApiTokens;
    use BelongsToTenant;

    protected $guarded = ['id'];
}
