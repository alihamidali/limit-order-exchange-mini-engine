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
        UserRepository $userRepo,
        AssetRepository $assetRepo,
        TradeRepository $tradeRepo
    ) {
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

            // Find counter order
            if ($newOrder->side === 'buy') {
                $counterOrder = $this->orderRepo->getOpenOrders($symbol)
                    ->where('side', 'sell')
                    ->first(fn($o) => $o->price <= $price && $o->amount == $amount);
            } else {
                $counterOrder = $this->orderRepo->getOpenOrders($symbol)
                    ->where('side', 'buy')
                    ->first(fn($o) => $o->price >= $price && $o->amount == $amount);
            }

            // No matching order found → leave new order open
            if (!$counterOrder) {
                return null;
            }

            // Matching order found → process trade
            $tradePrice = $counterOrder->price;
            $tradeAmount = $amount;
            $tradeValueUSD = $tradePrice * $tradeAmount;
            $commission = $tradeValueUSD * $this->commissionRate;

            // Assign buyer and seller
            if ($newOrder->side === 'buy') {
                $buyer = $newOrder->user;
                $seller = $counterOrder->user;
            } else {
                $buyer = $counterOrder->user;
                $seller = $newOrder->user;
            }

            // Update buyer: deduct commission
            $buyer->balance -= $commission;
            $buyer->save();

            // Update seller: unlock sold assets
            $sellerAsset = $this->assetRepo->getUserAsset($seller->id, $symbol);
            $sellerAsset->locked_amount -= $tradeAmount;
            $sellerAsset->save();

            // Update buyer assets
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

            // Update order statuses to filled
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

            // 🔹 Broadcast trade event via Pusher
            event(new OrderMatched($trade));

            return $trade;
        });
    }
}
