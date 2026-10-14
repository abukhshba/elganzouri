<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when uploaded sheet rows fail validation; nothing has been saved.
 */
class ImportValidationException extends Exception
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode("\n", $errors));
    }

    /**
     * The first few errors as one line each, for a notification body.
     */
    public function summary(int $limit = 10): string
    {
        $lines = array_slice($this->errors, 0, $limit);

        if (count($this->errors) > $limit) {
            $lines[] = __('erp.import.more_errors', ['count' => count($this->errors) - $limit]);
        }

        return implode('<br>', array_map('e', $lines));
    }
}
