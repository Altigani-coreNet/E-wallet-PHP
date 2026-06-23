<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Terminal;
use Illuminate\Http\Request;

class PosProfileController extends Controller
{
    /**
     * Lightweight POS profile endpoint
     * Public: returns limited merchant + user info for a given user ID.
     *
     * Request: GET /api/pos/profile/{user}
     * Params: user (path) - AuthService user ID
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "merchant": {
     *       "name": "...",
     *       "phone": "...",
     *       "address": "...",
     *       "email": "...",
     *       "code": "..."
     *     },
     *     "user": {
     *       "id": 123,
     *       "name": "...",
     *       "terminal_code": "TID123456"
     *     }
     *   }
     * }
     */
    public function showForPos(Request $request, $userId)
    {
        $user = User::with(['merchant', 'currentTerminal'])->find($userId);

        if (!$user || !$user->merchant) {
            return response()->json([
                'success' => false,
                'message' => 'User or merchant not found',
                'data' => null,
            ], 404);
        }

        $merchant = $user->merchant;

        return response()->json([
            'success' => true,
            'message' => 'POS profile fetched successfully',
            'data' => [
                'merchant' => [
                    'name' => $merchant->name ?? $merchant->business_name,
                    'phone' => $merchant->phone,
                    'address' => $merchant->address,
                    'email' => $merchant->email,
                    'code' => $merchant->merchant_code,
                    'tax_number' => $merchant->tax_number ?? null,
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'terminal_code' => $user->currentTerminal?->terminal_id,
                ],
            ],
        ]);
    }

    /**
     * Lightweight merchant + terminal lookup for POS/SoftPos
     * Public: accepts merchant_id and terminal_id (UUIDs from AuthService)
     *
     * Request: GET /api/pos/merchant-terminal?merchant_id=...&terminal_id=...
     */
    public function merchantTerminal(Request $request)
    {
        $merchantId = $request->query('merchant_id');
        $terminalId = $request->query('terminal_id');

        if (!$merchantId && !$terminalId) {
            return response()->json([
                'success' => false,
                'message' => 'merchant_id or terminal_id is required',
                'data' => null,
            ], 400);
        }

        $merchant = null;
        if ($merchantId) {
            $merchant = Merchant::find($merchantId);
        }

        $terminal = null;
        if ($terminalId) {
            $terminal = Terminal::find($terminalId);
        }

        if (!$merchant && !$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant or terminal not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Merchant and terminal fetched successfully',
            'data' => [
                'merchant' => $merchant ? [
                    'id' => $merchant->id,
                    'name' => $merchant->name ?? $merchant->business_name,
                    'business_name' => $merchant->business_name,
                    'code' => $merchant->merchant_code,
                    'phone' => $merchant->phone,
                    'email' => $merchant->email,
                    'address' => $merchant->address,
                ] : null,
                'terminal' => $terminal ? [
                    'id' => $terminal->id,
                    'terminal_id' => $terminal->terminal_id,
                    'name' => $terminal->name,
                    'status' => $terminal->terminal_status,
                ] : null,
            ],
        ]);
    }
}


