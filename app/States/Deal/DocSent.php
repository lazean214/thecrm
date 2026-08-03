<?php

namespace App\States\Deal;

use Spatie\ModelStates\States\StateConfig;

class DocSent extends DealState
{
    protected static string $name = 'doc sent';

    public static function config(): StateConfig
    {
        return parent::config()
            ->allowTransition(DocSigned::class)
            ->allowTransition(Lost::class);
    }

    public function label(): string
    {
        return 'Doc Sent';
    }
}
