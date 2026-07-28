<?php

namespace Database\Seeders;

use App\Models\DocumentNumberSequence;
use Illuminate\Database\Seeder;

class DocumentNumberSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sequences = [
            [
                'document_type' => 'PURCHASE',
                'prefix' => 'PO-',
                'suffix' => '',
                'padding' => 5,
                'current_number' => 0,
                'reset_period' => 'MONTHLY',
            ],
            [
                'document_type' => 'PURCHASE_RETURN',
                'prefix' => 'PR-',
                'suffix' => '',
                'padding' => 5,
                'current_number' => 0,
                'reset_period' => 'MONTHLY',
            ],
            [
                'document_type' => 'SALE',
                'prefix' => 'INV-',
                'suffix' => '',
                'padding' => 6,
                'current_number' => 0,
                'reset_period' => 'MONTHLY',
            ],
            [
                'document_type' => 'SALES_RETURN',
                'prefix' => 'SR-',
                'suffix' => '',
                'padding' => 5,
                'current_number' => 0,
                'reset_period' => 'MONTHLY',
            ],
            [
                'document_type' => 'TRANSFER',
                'prefix' => 'TR-',
                'suffix' => '',
                'padding' => 5,
                'current_number' => 0,
                'reset_period' => 'YEARLY',
            ],
            [
                'document_type' => 'ADJUSTMENT',
                'prefix' => 'ADJ-',
                'suffix' => '',
                'padding' => 5,
                'current_number' => 0,
                'reset_period' => 'YEARLY',
            ],
            [
                'document_type' => 'EXPENSE',
                'prefix' => 'EXP-',
                'suffix' => '',
                'padding' => 5,
                'current_number' => 0,
                'reset_period' => 'MONTHLY',
            ],
        ];

        foreach ($sequences as $sequence) {
            DocumentNumberSequence::updateOrCreate(
                ['document_type' => $sequence['document_type']],
                $sequence
            );
        }
    }
}
