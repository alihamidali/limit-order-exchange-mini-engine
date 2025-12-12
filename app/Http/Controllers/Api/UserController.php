<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\Order;

class UserController extends Controller
{
    /**
     * Return authenticated user's profile (USD balance + assets)
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        $assets = $user->assets()->get(['symbol', 'amount', 'locked_amount']);

        return response()->json([
            'success' => true,
            'user' => $user,
            'balance' => $user->balance,
            'assets' => $assets
        ]);
    }

    /**
     * Return user's orders (optional: filter by symbol)
     */
    public function orders(Request $request)
    {
        $user = $request->user();
        $symbol = $request->query('symbol');

        $query = $user->orders();

        if ($symbol) {
            $query->where('symbol', $symbol);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Return open orderbook for a given symbol
     */
    public function orderbook(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string|in:BTC,ETH'
        ]);

        $symbol = $request->symbol;

        $buyOrders = Order::where('symbol', $symbol)
            ->where('side', 'buy')
            ->where('status', 'open')
            ->orderByDesc('price')
            ->get();

        $sellOrders = Order::where('symbol', $symbol)
            ->where('side', 'sell')
            ->where('status', 'open')
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'buy' => $buyOrders,
            'sell' => $sellOrders
        ]);
    }
}
