<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function can_deposit_to_existing_user()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 100.00,
            'comment' => 'Test deposit',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'amount' => 100.00,
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 100.00,
            'comment' => 'Test deposit',
        ]);
    }

    public function can_deposit_to_new_user_balance_is_created()
    {
        $user = User::factory()->create();

        $this->assertDatabaseMissing('balances', ['user_id' => $user->id]);

        $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 50.00,
        ])->assertStatus(200);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'amount' => 50.00,
        ]);
    }

    public function cannot_deposit_negative_amount()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => -10.00,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function cannot_deposit_for_nonexistent_user()
    {
        $response = $this->postJson('/api/deposit', [
            'user_id' => 999999,
            'amount' => 100.00,
        ]);

        $response->assertStatus(422);
    }

    public function can_withdraw_if_sufficient_funds()
    {
        $user = User::factory()->create();
        $this->postJson('/api/deposit', ['user_id' => $user->id, 'amount' => 200.00]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 50.00,
            'comment' => 'Subscription',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('balances', ['user_id' => $user->id, 'amount' => 150.00]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'withdraw',
            'amount' => 50.00,
        ]);
    }

    public function cannot_withdraw_if_insufficient_funds()
    {
        $user = User::factory()->create();
        $this->postJson('/api/deposit', ['user_id' => $user->id, 'amount' => 100.00]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 150.00,
        ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Insufficient funds']);
    }

    public function cannot_withdraw_negative_amount()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => -50.00,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function can_transfer_between_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->postJson('/api/deposit', ['user_id' => $user1->id, 'amount' => 100.00]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $user1->id,
            'to_user_id' => $user2->id,
            'amount' => 30.00,
            'comment' => 'Gift',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('balances', ['user_id' => $user1->id, 'amount' => 70.00]);
        $this->assertDatabaseHas('balances', ['user_id' => $user2->id, 'amount' => 30.00]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user1->id,
            'type' => 'transfer_out',
            'amount' => 30.00,
            'related_user_id' => $user2->id,
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user2->id,
            'type' => 'transfer_in',
            'amount' => 30.00,
            'related_user_id' => $user1->id,
        ]);
    }

    public function cannot_transfer_to_self()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $user->id,
            'to_user_id' => $user->id,
            'amount' => 10.00,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['to_user_id']);
    }

    public function cannot_transfer_if_insufficient_funds()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->postJson('/api/deposit', ['user_id' => $user1->id, 'amount' => 50.00]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $user1->id,
            'to_user_id' => $user2->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Insufficient funds']);
    }

    public function cannot_transfer_to_nonexistent_user()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $user->id,
            'to_user_id' => 999999,
            'amount' => 10.00,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['to_user_id']);
    }

    public function get_balance_returns_correct_value()
    {
        $user = User::factory()->create();
        $this->postJson('/api/deposit', ['user_id' => $user->id, 'amount' => 250.00]);
        $this->postJson('/api/withdraw', ['user_id' => $user->id, 'amount' => 50.00]);

        $response = $this->getJson("/api/balance/{$user->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'user_id' => $user->id,
            'balance' => 200.00,
        ]);
    }

    public function get_balance_for_user_without_balance_record()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/balance/{$user->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);
    }

    public function race_condition_protection_works()
    {
        $user = User::factory()->create();
        $this->postJson('/api/deposit', ['user_id' => $user->id, 'amount' => 100.00]);

        $this->postJson('/api/withdraw', ['user_id' => $user->id, 'amount' => 40.00])->assertStatus(200);
        $this->postJson('/api/withdraw', ['user_id' => $user->id, 'amount' => 60.00])->assertStatus(200);

        $this->getJson("/api/balance/{$user->id}")->assertJson(['balance' => 0.00]);
    }
}