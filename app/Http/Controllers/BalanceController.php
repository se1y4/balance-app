<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Requests\TransferRequest;
use App\Services\BalanceService;
use App\Repositories\BalanceRepository;
use App\Http\Resources\BalanceResource;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    public function __construct(
        private readonly BalanceService $balanceService,
        private readonly BalanceRepository $balanceRepository
    ) {}

    public function deposit(DepositRequest $request): JsonResponse
    {
        try {
            $this->balanceService->deposit($request->toDto());
            return response()->json(['status' => 'ok']);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        try {
            $this->balanceService->withdraw($request->toDto());
            return response()->json(['status' => 'ok']);
        } catch (\DomainException $e) {
            return response()->json(['error' => 'Insufficient funds'], 409);
        }
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            $this->balanceService->transfer($request->toDto());
            return response()->json(['status' => 'ok']);
        } catch (\DomainException $e) {
            return response()->json(['error' => 'Insufficient funds'], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Invalid transfer'], 400);
        }
    }

    public function balance(int $userId): BalanceResource
    {
        $balance = $this->balanceRepository->getOrCreateByUserId($userId);
        return new BalanceResource($balance);
    }
}