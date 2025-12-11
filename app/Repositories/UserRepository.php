<?php


namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    protected $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function updateBalance(int $userId, float $amount): bool
    {
        $user = $this->model->lockForUpdate()->find($userId);
        if (!$user) return false;

        $user->balance = $amount;
        return $user->save();
    }
}
