<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class CalculatorController extends Controller
{
    public function index()
    {
        return view('public.calculator', [
            'contactIran' => Setting::get(Setting::WHATSAPP_IRAN),
            'contactUae' => Setting::get(Setting::WHATSAPP_UAE),
            'contactTehran' => Setting::get(Setting::TEHRAN_OFFICE_PHONE),
        ]);
    }
}
