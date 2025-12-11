<?php


namespace App\Services;
use App\Events\OrderMatched;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\AssetRepository;
use App\Repositories\TradeRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class MatchingService
{
    protected $orderRepo;
    protected $userRepo;
    protected $assetRepo;
    protected $tradeRepo;

    protected $commissionRate = 0.015; // 1.5%

    public function __construct(
        OrderRepository $orderRepo,
        UserRepository  $userRepo,
        AssetRepository $assetRepo,
        TradeRepository $tradeRepo
    )
    {
        $this->orderRepo = $orderRepo;
        $this->userRepo = $userRepo;
        $this->assetRepo = $assetRepo;
        $this->tradeRepo = $tradeRepo;
    }

    /**
     * Match new order with counter orders (full-match only)
     */
    public function matchOrder($newOrder)
    {
        return DB::transaction(function () use ($newOrder) {

            $symbol = $newOrder->symbol;
            $amount = $newOrder->amount;
            $price = $newOrder->price;

            if ($newOrder->side === 'buy') {
                // Match with first sell order where sell.price <= buy.price
                $counterOrder = $this->orderRepo->getOpenOrders($symbol)
                    ->where('side', 'sell')
                    ->first(fn($o) => $o->price <= $price && $o->amount == $amount);
            } else {
                // Match with first buy order where buy.price >= sell.price
                $counterOrder = $this->orderRepo->getOpenOrders($symbol)
                    ->where('side', 'buy')
                    ->first(fn($o) => $o->price >= $price && $o->amount == $amount);
            }

            if (!$counterOrder) {
                return null; // No match found
            }

            // Calculate trade price (we take price of the order being matched)
            $tradePrice = $counterOrder->price;
            $tradeAmount = $amount;
            $tradeValueUSD = $tradePrice * $tradeAmount;
            $commission = $tradeValueUSD * $this->commissionRate;

            // Update buyer and seller balances/assets
            if ($newOrder->side === 'buy') {
                $buyer = $newOrder->user;
                $seller = $counterOrder->user;
            } else {
                $buyer = $counterOrder->user;
                $seller = $newOrder->user;
            }

            // Update buyer: deduct USD + commission
            $buyer->balance -= $commission; // optional: commission from buyer
            $buyer->save();

            // Update seller: unlock assets and deduct commission if you want
            $sellerAsset = $this->assetRepo->getUserAsset($seller->id, $symbol);
            $sellerAsset->locked_amount -= $tradeAmount;
            $sellerAsset->save();

            // Buyer receives assets
            $buyerAsset = $this->assetRepo->getUserAsset($buyer->id, $symbol);
            if (!$buyerAsset) {
                $buyerAsset = $buyer->assets()->create([
                    'symbol' => $symbol,
                    'amount' => $tradeAmount,
                    'locked_amount' => 0,
                ]);
            } else {
                $buyerAsset->amount += $tradeAmount;
                $buyerAsset->save();
            }

            // Update order statuses
            $this->orderRepo->updateStatus($newOrder->id, 'filled');
            $this->orderRepo->updateStatus($counterOrder->id, 'filled');

            // Create trade record
            $trade = $this->tradeRepo->create([
                'buy_order_id' => $buyer->orders()->where('symbol', $symbol)->latest()->first()->id,
                'sell_order_id' => $seller->orders()->where('symbol', $symbol)->latest()->first()->id,
                'price' => $tradePrice,
                'amount' => $tradeAmount,
                'commission' => $commission,
            ]);
// 🔹 Broadcast the trade event via Pusher
            event(new OrderMatched($trade));
            return $trade;
        });
    }
}
