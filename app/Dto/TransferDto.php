<?php

namespace App\Dto;

class DepositDto
{
    public function __construct(
        public readonly int $from_user_id,
        public readonly int $to_user_id,
        public readonly float $amount,
        public readonly ?string $comment,
    ) {}
}