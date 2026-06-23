<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MerchantFormGetPasswordController extends Controller
{
    /**
     * Display the merchant forgot password form.
     */
    public function show(): View
    {
        return view('auth.forgot-password');
    }
}


