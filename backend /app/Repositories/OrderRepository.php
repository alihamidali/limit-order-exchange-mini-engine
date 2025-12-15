<?php


namespace App\Repositories;

use App\Models\Order;

class OrderRepository
{
    protected $model;

    public function __construct(Order $order)
    {
        $this->model = $order;
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function getOpenOrders(string $symbol)
    {
        return $this->model->where('symbol', $symbol)->where('status', 'open')->orderBy('created_at')->get();
    }

    public function updateStatus(int $orderId, string $status): bool
    {
        $order = $this->model->find($orderId);
        if (!$order) return false;

        $order->status = $status;
        return $order->save();
    }
    // ✅ Add this method:
    public function find(int $orderId): ?Order
    {
        return $this->model->find($orderId);
    }
}
