<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'symbol',
        'side',       // 'buy' or 'sell'
        'price',
        'amount',
        'status',     // 'open', 'filled', 'cancelled'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
    ];

    /**
     * Relationships
     */

    // Order belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Trades where this order was the BUY order
    public function buyTrades()
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    // Trades where this order was the SELL order
    public function sellTrades()
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }
}
