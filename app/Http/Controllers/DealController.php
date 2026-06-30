<?php

namespace App\Http\Controllers;

use App\Models\Deal;

class DealController extends Controller
{
    public function show($deal)
    {
        $deal = Deal::with(['contacts', 'companies', 'user', 'primaryContactRelation', 'primaryCompanyRelation'])
            ->findOrFail($deal);

        return view('deals.show', compact('deal'));
    }
}
