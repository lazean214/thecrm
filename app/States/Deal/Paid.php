<?php

namespace App\States\Deal;

use Spatie\ModelStates\States\StateConfig;

class Paid extends DealState
{
    protected static string $name = 'paid';

    public static function config(): StateConfig
    {
        return parent::config();
    }

    public function label(): string
    {
        return 'Paid';
    }
}
