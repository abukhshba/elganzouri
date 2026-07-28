<?php

namespace App\Traits;

use App\Services\DocumentNumberingService;

trait GeneratesDocumentCode
{
    /**
     * Boot the trait to set document code automatically before creation.
     */
    protected static function bootGeneratesDocumentCode(): void
    {
        static::creating(function ($model) {
            $codeColumn = $model->getDocumentCodeColumn();
            
            if (empty($model->{$codeColumn})) {
                $service = app(DocumentNumberingService::class);
                $model->{$codeColumn} = $service->generateNextCode($model->getDocumentType());
            }
        });
    }

    /**
     * Get the column name storing the document code.
     */
    public function getDocumentCodeColumn(): string
    {
        return property_exists($this, 'documentCodeColumn') ? $this->documentCodeColumn : 'document_number';
    }

    /**
     * Get the document type identifier for the numbering sequence.
     */
    abstract public function getDocumentType(): string;
}
