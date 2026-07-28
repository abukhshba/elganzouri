<?php

namespace App\Services;

use App\Models\DocumentNumberSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberingService
{
    /**
     * Generate the next sequential document code atomically.
     */
    public function generateNextCode(string $documentType): string
    {
        return DB::transaction(function () use ($documentType) {
            $sequence = DocumentNumberSequence::where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                // Fallback default sequence creation
                $sequence = DocumentNumberSequence::create([
                    'document_type' => $documentType,
                    'prefix' => strtoupper(substr($documentType, 0, 3)) . '-',
                    'suffix' => '',
                    'padding' => 5,
                    'current_number' => 0,
                    'reset_period' => 'NEVER',
                ]);
            }

            $now = Carbon::now();

            // Check if sequence needs reset (YEARLY or MONTHLY)
            if ($sequence->reset_period === 'YEARLY' && $sequence->last_reset_at && $sequence->last_reset_at->year !== $now->year) {
                $sequence->current_number = 0;
                $sequence->last_reset_at = $now;
            } elseif ($sequence->reset_period === 'MONTHLY' && $sequence->last_reset_at && ($sequence->last_reset_at->year !== $now->year || $sequence->last_reset_at->month !== $now->month)) {
                $sequence->current_number = 0;
                $sequence->last_reset_at = $now;
            }

            $nextNumber = $sequence->current_number + 1;
            $sequence->current_number = $nextNumber;
            $sequence->save();

            $formattedNumber = str_pad((string) $nextNumber, $sequence->padding, '0', STR_PAD_LEFT);

            return $sequence->prefix . $formattedNumber . $sequence->suffix;
        });
    }
}
