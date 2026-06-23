<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesController extends Controller
{
    /**
     * Display the Sales SPA
     * This handles all routes under merchant/sales/*
     */
    public function index()
    {
        // Get the authenticated user's API token
        $user = Auth::guard('external')->user();
        $apiToken = $user ? $user->getAccessToken() : null;
        // dd($apiToken);
        return view('sales.index', [
            'has_toolbar' => true,
            'api_token' => $apiToken
        ]);
    }
}

