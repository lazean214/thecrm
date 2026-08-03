<?php

namespace App\States\Deal;

use Spatie\ModelStates\States\StateConfig;

class Compliant extends DealState
{
    protected static string $name = 'compliant';

    public static function config(): StateConfig
    {
        return parent::config()
            ->allowTransition(ReadyForPayment::class)
            ->allowTransition(Lost::class);
    }

    public function label(): string
    {
        return 'Compliant';
    }
}
