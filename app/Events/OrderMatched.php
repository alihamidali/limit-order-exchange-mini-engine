<?php


namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Models\Trade;

class OrderMatched implements ShouldBroadcast
{
    use SerializesModels;

    public $trade;

    public function __construct(Trade $trade)
    {
        $this->trade = $trade;
    }

    // Broadcast to private channels of buyer and seller
    public function broadcastOn()
    {
        return [
            new PrivateChannel('user.' . $this->trade->buyOrder->user_id),
            new PrivateChannel('user.' . $this->trade->sellOrder->user_id),
            new Channel('orderbook.' . $this->trade->buyOrder->symbol),

        ];
    }

    public function broadcastWith()
    {
        return [
            'trade_id' => $this->trade->id,
            'buy_order_id' => $this->trade->buy_order_id,
            'sell_order_id' => $this->trade->sell_order_id,
            'buyer_id' => $this->trade->buyOrder->user_id,
            'seller_id' => $this->trade->sellOrder->user_id,
            'symbol' => $this->trade->buyOrder->symbol,
            'price' => $this->trade->price,
            'amount' => $this->trade->amount,
            'commission' => $this->trade->commission,
            'buy_order_status' => $this->trade->buyOrder->status,
            'sell_order_status' => $this->trade->sellOrder->status,
        ];
    }
}
