<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display the contract terms management page
     */
    public function contractTerms()
    {
        $setting = new Setting();
        $setting->terms_en = Setting::getTermsEn();
        $setting->terms_ar = Setting::getTermsAr();
        
        return view('admin.settings.contract-terms', compact('setting'));
    }

    /**
     * Update contract terms
     */
    public function updateTerms(Request $request)
    {
        $request->validate([
            'terms_en' => 'required|string',
            'terms_ar' => 'required|string',
        ]);

        try {
            Setting::setTermsEn($request->terms_en);
            Setting::setTermsAr($request->terms_ar);

            return redirect()->back()->with('success', __('translation.updated_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('translation.update_failed'));
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Setting $setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Setting $setting)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        //
    }
}
