<?php

namespace App\States\Deal;

use Spatie\ModelStates\States\StateConfig;

class ReadyForPayment extends DealState
{
    protected static string $name = 'ready for payment';

    public static function config(): StateConfig
    {
        return parent::config()
            ->allowTransition(Paid::class)
            ->allowTransition(Lost::class);
    }

    public function label(): string
    {
        return 'Ready for Payment';
    }
}
