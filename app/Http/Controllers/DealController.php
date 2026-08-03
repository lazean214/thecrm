<?php

namespace App\Http\Controllers;

use App\Models\Deal;

class DealController extends Controller
{
    public function show(Deal $deal)
    {
        $this->authorize('view', $deal);

        $deal->load(['contacts', 'companies', 'user', 'primaryContactRelation', 'primaryCompanyRelation']);

        return view('deals.show', compact('deal'));
    }
}
