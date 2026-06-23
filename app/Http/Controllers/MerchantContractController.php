<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class MerchantContractController extends Controller
{
    public function index(Request $request)
    {
        $merchant = auth()->user()->merchant;
        $contract_terms_en = Setting::where('key', 'contract_terms_en')->first();
        $contract_terms_ar = Setting::where('key', 'contract_terms_ar')->first();
        
        // Handle language switching
        if ($request->has('locale') && in_array($request->locale, ['en', 'ar'])) {
            app()->setLocale($request->locale);
        }
        
        // // Get contract terms from settings based on current locale
        // $terms = $settings->contract_terms;
        // if (app()->getLocale() === 'ar') {
        //     $terms = $settings->contract_terms_ar;
        // }
        $merchant->payment_status = 'paid'	;
        
        return view('merchant.contracts.index', [
            'merchant' => $merchant,
            // 'terms' => $terms, 
            'contract_terms_en' => $contract_terms_en,
            'contract_terms_ar' => $contract_terms_ar,
            'has_toolbar' => true	
        ]);
    }
}
