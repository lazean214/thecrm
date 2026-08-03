<?php

namespace App\States\Deal;

use Spatie\ModelStates\State;
use Spatie\ModelStates\States\StateConfig;

abstract class DealState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(DocSent::class);
    }
}
