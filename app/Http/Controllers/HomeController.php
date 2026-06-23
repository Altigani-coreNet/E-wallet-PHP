<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(){
        return view('landing.index');
    }

    public function terms(){
        $locale = app()->getLocale(); // Get current language
        $terms = \App\Models\Setting::getContractTerms($locale);
        return view('landing.terms', [
            'terms' => $terms,
            'locale' => $locale
        ]);
    }

    public function privacy(){
        return view('landing.privacy');
    }
}
