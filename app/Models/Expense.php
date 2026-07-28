<?php

namespace App\Models;

use App\Casts\DecimalCast;
use App\Traits\GeneratesDocumentCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, GeneratesDocumentCode;

    protected string $documentCodeColumn = 'expense_number';

    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'cashbox_id',
        'amount',
        'expense_date',
        'user_id',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'amount' => DecimalCast::class,
        'expense_date' => 'date',
    ];

    public function getDocumentType(): string
    {
        return 'EXPENSE';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
