<?php
namespace App\Services;
use App\Repositories\UserRepository;
use App\Repositories\AssetRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    protected $userRepo;
    protected $assetRepo;
    protected $orderRepo;

    public function __construct(
        UserRepository  $userRepo,
        AssetRepository $assetRepo,
        OrderRepository $orderRepo
    )
    {
        $this->userRepo = $userRepo;
        $this->assetRepo = $assetRepo;
        $this->orderRepo = $orderRepo;
    }

    /**
     * Place a Buy Order
     */
    public function placeBuyOrder(int $userId, string $symbol, float $price, float $amount)
    {
        return DB::transaction(function () use ($userId, $symbol, $price, $amount) {

            $user = $this->userRepo->findById($userId);
            if (!$user) throw new Exception("User not found.");

            $totalCost = $price * $amount;

            if ($user->balance < $totalCost) {
                throw new Exception("Insufficient USD balance.");
            }

            // Deduct USD balance (lock)
            $user->balance -= $totalCost;
            $user->save();

            // Create Order
            $order = $this->orderRepo->create([
                'user_id' => $userId,
                'symbol' => $symbol,
                'side' => 'buy',
                'price' => $price,
                'amount' => $amount,
                'status' => 'open',
            ]);

            return $order;
        });
    }

    /**
     * Place a Sell Order
     */
    public function placeSellOrder(int $userId, string $symbol, float $price, float $amount)
    {
        return DB::transaction(function () use ($userId, $symbol, $price, $amount) {

            $asset = $this->assetRepo->getUserAsset($userId, $symbol);
            if (!$asset) throw new Exception("Asset not found.");

            if ($asset->amount < $amount) {
                throw new Exception("Insufficient asset balance.");
            }

            // Lock asset amount
            $asset->amount -= $amount;
            $asset->locked_amount += $amount;
            $asset->save();

            // Create Order
            $order = $this->orderRepo->create([
                'user_id' => $userId,
                'symbol' => $symbol,
                'side' => 'sell',
                'price' => $price,
                'amount' => $amount,
                'status' => 'open',
            ]);

            return $order;
        });
    }
}
