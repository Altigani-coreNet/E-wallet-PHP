<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Plan;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MerchantPlanController extends Controller
{
    use ApiResponse;

    /**
     * Upgrade or change the authenticated merchant's plan.
     */
    public function upgrade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage('Validation failed', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return $this->ErrorMessage('User not authenticated', null, 401);
            }

            /** @var Merchant|null $merchant */
            $merchant = $user->merchant;
            if (!$merchant) {
                return $this->ErrorMessage('Merchant profile not found', null, 404);
            }

            $planId = $request->input('plan_id');

            // Optionally ensure plan is active
            $plan = Plan::where('status', true)->findOrFail($planId);

            $merchant->plan_id = $plan->id;
            $merchant->save();

            $merchant->load('plan');

            return $this->SuccessMessage([
                'message' => 'Plan updated successfully',
                'merchant' => $merchant,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upgrade plan: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->ErrorMessage('Failed to upgrade plan: ' . $e->getMessage(), null, 500);
        }
    }
}


