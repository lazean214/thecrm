<?php

namespace App\Http\Controllers;

use App\Models\Company;

class CompanyController extends Controller
{
    public function index()
    {
        return view('companies');
    }

    public function show(Company $company)
    {
        return view('companies.show', compact('company'));
    }
}
