<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Services\MatchingService;
use App\Repositories\OrderRepository;
use Exception;

class OrderController extends Controller
{
    protected $orderService;
    protected $matchingService;
    protected $orderRepo;

    public function __construct(
        OrderService $orderService,
        MatchingService $matchingService,
        OrderRepository $orderRepo
    ) {
        $this->orderService = $orderService;
        $this->matchingService = $matchingService;
        $this->orderRepo = $orderRepo;
    }

    /**
     * Place a new order (buy/sell)
     */
    public function store(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string|in:BTC,ETH',
            'side' => 'required|string|in:buy,sell',
            'price' => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $userId = $request->user()->id;
        $symbol = $request->symbol;
        $side = $request->side;
        $price = $request->price;
        $amount = $request->amount;

        try {
            if ($side === 'buy') {
                $order = $this->orderService->placeBuyOrder($userId, $symbol, $price, $amount);
            } else {
                $order = $this->orderService->placeSellOrder($userId, $symbol, $price, $amount);
            }

            return response()->json([
                'success' => true,
                'order' => $order
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel an order
     */
    public function cancel($id)
    {
        try {
            $order = $this->orderRepo->find($id);
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            if ($order->status !== 'open') {
                return response()->json(['success' => false, 'message' => 'Order cannot be cancelled'], 400);
            }

            // Refund user balance or unlock assets
            if ($order->side === 'buy') {
                $order->user->balance += $order->price * $order->amount;
                $order->user->save();
            } else {
                $asset = $order->user->assets()->where('symbol', $order->symbol)->first();
                $asset->amount += $order->amount;
                $asset->locked_amount -= $order->amount;
                $asset->save();
            }

            $this->orderRepo->updateStatus($id, 'cancelled');

            return response()->json(['success' => true, 'message' => 'Order cancelled']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Trigger order matching manually
     */
    public function match(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer'
        ]);

        $order = $this->orderRepo->find($request->order_id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        try {
            $trade = $this->matchingService->matchOrder($order);

            if (!$trade) {
                return response()->json(['success' => false, 'message' => 'No matching order found']);
            }

            return response()->json([
                'success' => true,
                'trade' => $trade
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
