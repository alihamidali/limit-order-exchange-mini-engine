<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'buy_order_id',
        'sell_order_id',
        'price',
        'amount',
        'commission',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'commission' => 'decimal:8',
    ];

    /**
     * Relationships
     */

    // Trade belongs to the buy order
    public function buyOrder()
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    // Trade belongs to the sell order
    public function sellOrder()
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }
}
