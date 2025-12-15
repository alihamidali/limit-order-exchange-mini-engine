<?php


namespace App\Repositories;

use App\Models\Asset;

class AssetRepository
{
    protected $model;

    public function __construct(Asset $asset)
    {
        $this->model = $asset;
    }

    public function getUserAsset(int $userId, string $symbol): ?Asset
    {
        return $this->model->where('user_id', $userId)->where('symbol', $symbol)->lockForUpdate()->first();
    }

    public function updateAmounts(int $assetId, float $amount, float $lockedAmount): bool
    {
        $asset = $this->model->find($assetId);
        if (!$asset) return false;

        $asset->amount = $amount;
        $asset->locked_amount = $lockedAmount;
        return $asset->save();
    }
}
