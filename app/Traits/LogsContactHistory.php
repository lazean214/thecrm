<?php

// app/Traits/LogsContactHistory.php

namespace App\Traits;

trait LogsContactHistory
{
    public function logContactChanges($original)
    {
        $changes = [];
        $fields = ['first_name', 'last_name', 'email', 'phone', 'gender', 'date_of_birth',
            'marital_status', 'street_address', 'city', 'state', 'postal_code',
            'country', 'ni_number', 'bank', 'account_number', 'sort_code'];

        foreach ($fields as $field) {
            if ($this->$field != $original->$field) {
                $changes[$field] = [$original->$field, $this->$field];
            }
        }

        if (! empty($changes) && $this->deals) {
            foreach ($this->deals as $deal) {
                foreach ($changes as $field => [$old, $new]) {
                    $deal->logFieldUpdate("contact_{$field}", $old, $new,
                        "Contact {$field} updated from \"{$old}\" to \"{$new}\"");
                }
            }
        }
    }
}
