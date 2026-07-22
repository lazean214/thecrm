<?php

namespace App\Http\Controllers;

use App\Models\Remittance;

class RemittanceController extends Controller
{
    public function index()
    {
        $remittances = Remittance::all();

        return view('remittances.index', compact('remittances'));
    }

    public function create()
    {
        return view('remittances.create');
    }
}
