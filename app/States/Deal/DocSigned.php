<?php

namespace App\States\Deal;

use Spatie\ModelStates\States\StateConfig;

class DocSigned extends DealState
{
    protected static string $name = 'doc signed';

    public static function config(): StateConfig
    {
        return parent::config()
            ->allowTransition(Compliant::class)
            ->allowTransition(Lost::class);
    }

    public function label(): string
    {
        return 'Doc Signed';
    }
}
