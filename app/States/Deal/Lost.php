<?php

namespace App\States\Deal;

use Spatie\ModelStates\States\StateConfig;

class Lost extends DealState
{
    protected static string $name = 'lost';

    public static function config(): StateConfig
    {
        return parent::config();
    }

    public function label(): string
    {
        return 'Lost';
    }
}
