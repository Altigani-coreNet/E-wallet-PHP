<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Merchant;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function contractTerms()
    {
        // $setting = Setting::first();
        $terms_ar = Setting::where('key', 'contract_terms_ar')->first();
        $terms_en = Setting::where('key', 'contract_terms_en')->first();

        return view('admin.settings.contract-terms', compact('terms_ar', 'terms_en'));
    }

    public function updateTerms(Request $request)
    {
        Setting::updateContractSettings($request->all());
        // dd($request->all());
        return redirect()->back()->with('success', __('translation.updated_successfully'));
    }

    public function previewTerms($lang)
    {
        $setting = Setting::first();
        $terms_ar = Setting::where('key', 'contract_terms_ar')->first();
        $terms_en = Setting::where('key', 'contract_terms_en')->first();

        $terms = $lang === 'en' ? $terms_en->value : $terms_ar->value;
        
        // Auto-download if download parameter is present
        if (request()->has('download')) {
            return response()->view('admin.settings.preview-terms', compact('terms', 'merchant'))
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="merchant_agreement.html"');
        }
        
        // Get the merchant data if merchant_id is provided, otherwise use demo data
        $merchant = request()->has('merchant_id') 
            ? Merchant::findOrFail(request()->merchant_id)
            : (object)[
                'name' => 'DEMO MERCHANT NAME',
                'company_name' => 'DEMO COMPANY NAME',
                'merchant_code' => 'MERCH123456',
                'cr_number' => '1234567890',
                'trade_license_number' => 'TL123456789',
                'vat_number' => 'VAT123456789',
                'country' => (object)['name' => 'United Arab Emirates'],
                'city' => (object)['name' => 'Dubai'],
                'address' => 'Demo Street, Building 123',
                'phone' => '+971 50 123 4567',
                'email' => 'demo@merchant.com'
            ];

        return view('admin.settings.preview-terms', compact('terms', 'merchant'));
    }
}
