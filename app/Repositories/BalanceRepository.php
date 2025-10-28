<?php

namespace App\Repositories;

use App\Models\Balance;
use Illuminate\Support\Facades\DB;

class BalanceRepository
{
    public function getOrCreateByUserId(int $userId): Balance
    {
        return Balance::firstOrCreate(['user_id' => $userId], ['amount' => 0]);
    }

    public function lockByUserId(int $userId): Balance
    {
        $balance = DB::select('SELECT * FROM balances WHERE user_id = ? FOR UPDATE', [$userId]);
        if (empty($balance)) {
            return $this->getOrCreateByUserId($userId);
        }
        return Balance::hydrate([$balance[0]])[0];
    }

    public function logTransaction(int $userId, string $type, float $amount, ?int $relatedUserId, ?string $comment): void
    {
        DB::table('transactions')->insert([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'related_user_id' => $relatedUserId,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }
}