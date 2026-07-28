<?php

namespace Tests\Feature;

use App\Actions\Treasury\RecordExpenseAction;
use App\Enums\CashboxTransactionType;
use App\Exceptions\CashboxBalanceException;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\TreasuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Milestone6Test extends TestCase
{
    use RefreshDatabase;

    public function test_treasury_service_inflow_and_outflow(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::create(['name' => 'Main Register', 'current_balance' => 500.0]);

        $service = app(TreasuryService::class);

        // Cash IN $200 -> Balance = $700
        $txIn = $service->postTransaction($cashbox->id, CashboxTransactionType::IN, 200.0, $user, $user->id, 'Deposit');

        $this->assertEquals(700.0, $cashbox->fresh()->current_balance);
        $this->assertEquals(500.0, $txIn->balance_before);
        $this->assertEquals(700.0, $txIn->balance_after);

        // Cash OUT $300 -> Balance = $400
        $txOut = $service->postTransaction($cashbox->id, CashboxTransactionType::OUT, 300.0, $user, $user->id, 'Withdrawal');

        $this->assertEquals(400.0, $cashbox->fresh()->current_balance);
        $this->assertEquals(700.0, $txOut->balance_before);
        $this->assertEquals(400.0, $txOut->balance_after);
        $this->assertEquals(2, CashboxTransaction::count());
    }

    public function test_cashbox_overdraft_protection_throws_exception(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::create(['name' => 'Main Register', 'current_balance' => 100.0]);

        $service = app(TreasuryService::class);

        $this->expectException(CashboxBalanceException::class);

        // Attempting OUT $150 > Available $100
        $service->postTransaction($cashbox->id, CashboxTransactionType::OUT, 150.0, $user, $user->id, 'Overdraft Outflow');
    }

    public function test_record_expense_action_creates_voucher_and_posts_outflow(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::create(['name' => 'Register #1', 'current_balance' => 1000.0]);
        $category = ExpenseCategory::create(['name' => 'Utilities', 'code' => 'EXP-UTIL']);

        $action = app(RecordExpenseAction::class);

        $expense = $action->execute(
            expenseCategoryId: $category->id,
            cashboxId: $cashbox->id,
            amount: 250.0,
            expenseDate: '2026-07-28',
            userId: $user->id,
            notes: 'Electricity bill'
        );

        $this->assertEquals('EXP-00001', $expense->expense_number);
        $this->assertEquals(750.0, $cashbox->fresh()->current_balance); // $1000 - $250
        $this->assertEquals(1, CashboxTransaction::count());
    }
}
