<?php

namespace Tests\Feature;

use App\Enums\CashboxTransactionType;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseFormTest extends TestCase
{
    use RefreshDatabase;

    protected Cashbox $cashbox;

    protected ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->cashbox = Cashbox::create(['name' => 'Main Cashbox', 'current_balance' => 1000.0]);
        $this->category = ExpenseCategory::create(['name' => 'Electricity', 'code' => 'ELEC']);
    }

    public function test_expense_is_paid_from_the_cashbox_and_logged(): void
    {
        Livewire::test(CreateExpense::class)
            ->assertSchemaStateSet(['cashbox_id' => $this->cashbox->id])
            ->fillForm([
                'expense_category_id' => $this->category->id,
                'amount' => 250,
                'expense_date' => now()->toDateString(),
                'notes' => 'October bill',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $expense = Expense::sole();
        $this->assertNotEmpty($expense->expense_number);
        $this->assertEquals('October bill', $expense->notes);
        $this->assertEquals(750.0, (float) $this->cashbox->fresh()->current_balance);

        $transaction = CashboxTransaction::sole();
        $this->assertEquals(CashboxTransactionType::OUT, $transaction->transaction_type);
        $this->assertEquals(250.0, (float) $transaction->amount);
        $this->assertEquals(Expense::class, $transaction->reference_type);
        $this->assertEquals($expense->id, $transaction->reference_id);

        Livewire::test(ListExpenses::class)->assertCanSeeTableRecords([$expense]);
    }

    public function test_expense_blocked_when_cashbox_is_short(): void
    {
        Livewire::test(CreateExpense::class)
            ->fillForm([
                'expense_category_id' => $this->category->id,
                'amount' => 5000,
                'expense_date' => now()->toDateString(),
            ])
            ->call('create')
            ->assertNotified(__('erp.actions.expense_failed'));

        $this->assertEquals(0, Expense::count());
        $this->assertEquals(0, CashboxTransaction::count());
        $this->assertEquals(1000.0, (float) $this->cashbox->fresh()->current_balance);
    }
}
