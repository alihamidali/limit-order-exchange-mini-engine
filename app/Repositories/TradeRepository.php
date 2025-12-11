<?php


namespace App\Repositories;

use App\Models\Trade;

class TradeRepository
{
    protected $model;

    public function __construct(Trade $trade)
    {
        $this->model = $trade;
    }

    public function create(array $data): Trade
    {
        return $this->model->create($data);
    }
}
