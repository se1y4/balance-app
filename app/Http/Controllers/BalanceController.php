<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Requests\TransferRequest;
use App\Services\BalanceService;
use App\Repositories\BalanceRepository;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    public function deposit(DepositRequest $request, BalanceService $service): JsonResponse
    {
        try {
            $service->deposit(
                $request->user_id,
                $request->amount,
                $request->comment
            );
            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    public function withdraw(WithdrawRequest $request, BalanceService $service): JsonResponse
    {
        try {
            $service->withdraw(
                $request->user_id,
                $request->amount,
                $request->comment
            );
            return response()->json(['status' => 'ok']);
        } catch (\DomainException $e) {
            return response()->json(['error' => 'Insufficient funds'], 409);
        }
    }

    public function transfer(TransferRequest $request, BalanceService $service): JsonResponse
    {
        try {
            $service->transfer(
                $request->from_user_id,
                $request->to_user_id,
                $request->amount,
                $request->comment
            );
            return response()->json(['status' => 'ok']);
        } catch (\DomainException $e) {
            return response()->json(['error' => 'Insufficient funds'], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Invalid transfer'], 400);
        }
    }

    public function balance(int $userId, BalanceRepository $repo): JsonResponse
    {
        $balance = $repo->getOrCreateByUserId($userId);
        return response()->json([
            'user_id' => $userId,
            'balance' => (float) $balance->amount,
        ]);
    }
}