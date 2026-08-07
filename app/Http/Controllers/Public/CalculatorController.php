<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class CalculatorController extends Controller
{
    public function index()
    {
        return view('public.calculator');
    }
}
