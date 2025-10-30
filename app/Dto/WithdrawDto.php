<?php

namespace App\Dto;

class DepositDto
{
    public function __construct(
        public readonly int $userId,
        public readonly float $amount,
        public readonly ?string $comment,
    ) {}
}