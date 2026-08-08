<?php

namespace App\Traits;

trait HasBilingualFields
{
    public function getNameArAttribute(): string
    {
        return $this->getTranslation('name', 'ar', false) ?: '';
    }

    public function getNameEnAttribute(): string
    {
        return $this->getTranslation('name', 'en', false) ?: '';
    }

    public function setNameArAttribute($value): void
    {
        $this->setTranslation('name', 'ar', $value ?? '');
    }

    public function setNameEnAttribute($value): void
    {
        $this->setTranslation('name', 'en', $value ?? '');
    }

    public function getDescriptionArAttribute(): string
    {
        return $this->getTranslation('description', 'ar', false) ?: '';
    }

    public function getDescriptionEnAttribute(): string
    {
        return $this->getTranslation('description', 'en', false) ?: '';
    }

    public function setDescriptionArAttribute($value): void
    {
        $this->setTranslation('description', 'ar', $value ?? '');
    }

    public function setDescriptionEnAttribute($value): void
    {
        $this->setTranslation('description', 'en', $value ?? '');
    }
}
