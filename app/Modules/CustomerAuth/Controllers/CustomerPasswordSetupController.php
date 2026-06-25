<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Modules\CustomerAuth\Services\CustomerPasswordSetupService;
use App\Support\SuccessResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerPasswordSetupController
{
    public function __construct(
        private readonly CustomerPasswordSetupService $setupService,
    ) {}

    public function validateToken(Request $request)
    {
        $token = (string) $request->query('token', '');

        $record = $this->setupService->findActiveByPlainToken($token);

        if (! $record) {
            return SuccessResponse::error('Invalid or expired token', 400);
        }

        return SuccessResponse::make([
            'valid' => true,
        ], 'Token is valid');
    }

    public function setPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $this->setupService->setPassword($validated['token'], $validated['password']);

        return SuccessResponse::make([
            'password_set' => true,
        ], 'Password set successfully. You can now log in.');
    }
}
