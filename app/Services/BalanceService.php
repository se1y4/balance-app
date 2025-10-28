<?php

namespace App\Services;

use App\Repositories\BalanceRepository;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public function __construct(private BalanceRepository $repo) {}

    public function deposit(int $userId, float $amount, ?string $comment = null): void
    {
        DB::transaction(function () use ($userId, $amount, $comment) {
            $balance = $this->repo->lockByUserId($userId);
            $balance->amount += $amount;
            $balance->save();

            $this->repo->logTransaction($userId, 'deposit', $amount, null, $comment);
        });
    }

    public function withdraw(int $userId, float $amount, ?string $comment = null): void
    {
        DB::transaction(function () use ($userId, $amount, $comment) {
            $balance = $this->repo->lockByUserId($userId);
            if ($balance->amount < $amount) {
                throw new \DomainException('Insufficient funds');
            }
            $balance->amount -= $amount;
            $balance->save();

            $this->repo->logTransaction($userId, 'withdraw', $amount, null, $comment);
        });
    }

    public function transfer(int $fromId, int $toId, float $amount, ?string $comment = null): void
    {
        if ($fromId === $toId) {
            throw new \InvalidArgumentException('Cannot transfer to self');
        }

        DB::transaction(function () use ($fromId, $toId, $amount, $comment) {
            $firstId = min($fromId, $toId);
            $secondId = max($fromId, $toId);

            $balance1 = $this->repo->lockByUserId($firstId);
            $balance2 = $this->repo->lockByUserId($secondId);

            $sender = $fromId === $firstId ? $balance1 : $balance2;
            $receiver = $toId === $firstId ? $balance1 : $balance2;

            if ($sender->amount < $amount) {
                throw new \DomainException('Insufficient funds');
            }

            $sender->amount -= $amount;
            $receiver->amount += $amount;
            $sender->save();
            $receiver->save();

            $this->repo->logTransaction($fromId, 'transfer_out', $amount, $toId, $comment);
            $this->repo->logTransaction($toId, 'transfer_in', $amount, $fromId, $comment);
        });
    }
}