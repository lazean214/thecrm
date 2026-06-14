<?php

namespace Modules\Signable\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SendEnvelopeController extends Controller
{
    public function __invoke(): View
    {
        return view('signable::envelopes.send');
    }
}
