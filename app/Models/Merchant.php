<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $domain
 * @property string $display_name
 * @property bool $turn_customers_into_affiliates
 * @property User $user
 * @property float $default_commission_rate
 */
class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain',
        'display_name',
        'turn_customers_into_affiliates',
        'default_commission_rate',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function affiliates(): HasMany
    {
        return $this->hasMany(Affiliate::class);
    }
}
