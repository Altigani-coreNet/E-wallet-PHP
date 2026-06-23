<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OperatorMiddlewareService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PartnerController extends Controller
{
    // Response Code Constants
    const RESPONSE_SUCCESS = 1;
    const RESPONSE_INTERNAL_ERROR = 0;
    const RESPONSE_INVALID_PARAMETERS = 101;
    const RESPONSE_TOKEN_INVALID = 102;
    const RESPONSE_TOKEN_NOT_FOUND = 103;
    const RESPONSE_CHECK_PARAMETER = 104;
    const RESPONSE_INVALID_CREDENTIALS = 109;
    const RESPONSE_UNSUBSCRIBE_FAILED = 114;
    const RESPONSE_ALREADY_CANCELED = 116;
    const RESPONSE_ALREADY_SUBSCRIBED = 121;

    protected OperatorMiddlewareService $middlewareService;

    public function __construct(OperatorMiddlewareService $middlewareService)
    {
        $this->middlewareService = $middlewareService;
    }

    /**
     * Get Public Key Service
     * PATH: /SPayAPI/Service/GetPublicKey/
     * Method: POST
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getPublicKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'providerKey' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_INVALID_PARAMETERS,
                'responseMessage' => 'please check your parameter or method',
                'publicKey' => null,
            ], 400);
        }

        // Get public key - for now return a mock response
        // In the future, this can be stored in database or config
        $result = [
            'success' => true,
            'responseCode' => 1,
            'responseMessage' => 'OK',
            'publicKey' => config('services.spay.public_key', 'MOCK_PUBLIC_KEY'),
        ];

        return response()->json([
            'status' => $result['success'],
            'responseCode' => self::RESPONSE_SUCCESS,
            'responseMessage' => $result['responseMessage'],
            'publicKey' => $result['publicKey'],
        ], 200);
    }

    /**
     * Authenticate with SPAY and store credentials for the authenticated user
     * PATH: /api/service/authenticate-spay
     * Method: POST
     * Requires: Passport authentication
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticateSpay(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_TOKEN_NOT_FOUND,
                'responseMessage' => 'The token not found, please login',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_INVALID_PARAMETERS,
                'responseMessage' => 'please check your parameter or method',
                'token' => null,
                'expireDate' => null,
            ], 400);
        }

        $result = $this->middlewareService->handleLogin($request, $user);

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_INVALID_CREDENTIALS,
                'responseMessage' => $result['responseMessage'] ?? 'Please check your credentials.',
                'token' => null,
                'expireDate' => null,
            ], 200);
        }

        return response()->json([
            'status' => true,
            'responseCode' => self::RESPONSE_SUCCESS,
            'responseMessage' => $result['responseMessage'],
            'token' => $result['token'],
            'expireDate' => $result['expireDate'],
        ], 200);
    }

    /**
     * Check Subscription Service
     * PATH: /SPayAPI/Service/CheckSubscription/
     * Method: POST
     * Requires: Passport authentication
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkSubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_TOKEN_NOT_FOUND,
                'responseMessage' => 'The token not found, please login',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'msisdn' => 'required|string',
            'serviceCode' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_INVALID_PARAMETERS,
                'responseMessage' => 'please check your parameter',
            ], 400);
        }

        $result = $this->middlewareService->handleCheckSubscription($request, $user);

        if (!$result['success']) {
            // Check if it's a token error
            if (in_array($result['responseCode'] ?? 0, [self::RESPONSE_TOKEN_INVALID, self::RESPONSE_TOKEN_NOT_FOUND])) {
                $responseCode = ($result['responseCode'] == self::RESPONSE_TOKEN_INVALID) 
                    ? self::RESPONSE_TOKEN_INVALID 
                    : self::RESPONSE_TOKEN_NOT_FOUND;
                
                return response()->json([
                    'status' => false,
                    'responseCode' => $responseCode,
                    'responseMessage' => $result['responseMessage'],
                ], 401);
            }

            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_INTERNAL_ERROR,
                'responseMessage' => $result['responseMessage'] ?? 'the subscriber is not active',
            ], 200);
        }

        return response()->json([
            'status' => true,
            'responseCode' => self::RESPONSE_SUCCESS,
            'responseMessage' => $result['responseMessage'],
            'unSubDate' => $result['unSubDate'],
        ], 200);
    }

    /**
     * Unsubscribe Service
     * PATH: /SPayAPI/Service/UnSubscribe/
     * Method: POST
     * Requires: Passport authentication
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_TOKEN_NOT_FOUND,
                'responseMessage' => 'The token not found, please login',
                'isSent' => false,
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'msisdn' => 'required|string',
            'serviceCode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_INVALID_PARAMETERS,
                'responseMessage' => 'please check your parameter',
                'isSent' => false,
            ], 400);
        }

        $result = $this->middlewareService->handleUnsubscribe($request, $user);

        if (!$result['success']) {
            // Check if it's a token error
            if (in_array($result['responseCode'] ?? 0, [self::RESPONSE_TOKEN_INVALID, self::RESPONSE_TOKEN_NOT_FOUND])) {
                $responseCode = ($result['responseCode'] == self::RESPONSE_TOKEN_INVALID) 
                    ? self::RESPONSE_TOKEN_INVALID 
                    : self::RESPONSE_TOKEN_NOT_FOUND;
                
                return response()->json([
                    'status' => false,
                    'responseCode' => $responseCode,
                    'responseMessage' => $result['responseMessage'],
                    'isSent' => false,
                ], 401);
            }

            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_UNSUBSCRIBE_FAILED,
                'responseMessage' => $result['responseMessage'] ?? 'fail to UnSubscribe',
                'isSent' => false,
            ], 200);
        }

        return response()->json([
            'status' => true,
            'responseCode' => self::RESPONSE_SUCCESS,
            'responseMessage' => $result['responseMessage'],
            'isSent' => $result['isSent'],
        ], 200);
    }

    /**
     * Subscribe Service
     * PATH: /SPayAPI/Service/Subscribe/
     * Method: POST
     * Requires: Passport authentication
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_TOKEN_NOT_FOUND,
                'responseMessage' => 'The token not found, please login',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'msisdn' => 'required|string',
            'serviceCode' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_INVALID_PARAMETERS,
                'responseMessage' => 'please check your parameter',
            ], 400);
        }

        $result = $this->middlewareService->handleSubscribe($request, $user);

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'responseCode' => self::RESPONSE_INTERNAL_ERROR,
                'responseMessage' => $result['responseMessage'] ?? 'Subscription failed',
            ], 200);
        }

        return response()->json([
            'status' => true,
            'responseCode' => self::RESPONSE_SUCCESS,
            'responseMessage' => $result['responseMessage'],
            'subscription_id' => $result['subscription_id'] ?? null,
        ], 201);
    }
}
