<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Traits\Select2Trait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    use Select2Trait;

    /**
     * Return customers for select2 dropdown
     */
    public function select(Request $request): JsonResponse
    {
        $query = Customer::query();
        return $this->getSelect2DataInNormalSearch($request, $query, ['name']);
    }


    public function storeAjax(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
        ]);

        $customer = Customer::create($request->except('_token'));

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer
        ]);
    }
}
