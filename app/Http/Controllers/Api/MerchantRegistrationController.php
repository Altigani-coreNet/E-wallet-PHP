<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MerchantRegistrationRequest;
use App\Repositories\MerchantRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Merchants",
 *     description="API Endpoints for merchant management"
 * )
 */
class MerchantRegistrationController extends Controller
{
    protected $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }

    /**
     * @OA\Post(
     *     path="/api/merchants/register",
     *     summary="Register a new merchant",
     *     description="Register a new merchant with all required details and documents",
     *     operationId="merchantRegister",
     *     tags={"Merchants"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"first_name","last_name","email","phone","owner_name","business_name","business_type","business_address","temp_merchant_code"},
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                     maxLength=255,
     *                     example="John",
     *                     description="First name of the merchant"
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                     maxLength=255,
     *                     example="Doe",
     *                     description="Last name of the merchant"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     maxLength=255,
     *                     example="john@example.com",
     *                     description="Email address for the merchant account (must be unique)"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     maxLength=20,
     *                     example="+1234567890",
     *                     description="Contact phone number (must be unique)"
     *                 ),
     *                 @OA\Property(
     *                     property="owner_name",
     *                     type="string",
     *                     maxLength=255,
     *                     example="John Doe",
     *                     description="Name of the business owner"
     *                 ),
     *                 @OA\Property(
     *                     property="business_name",
     *                     type="string",
     *                     maxLength=255,
     *                     example="My Business Name",
     *                     description="Name of the business"
     *                 ),
     *                 @OA\Property(
     *                     property="business_type",
     *                     type="string",
     *                     maxLength=255,
     *                     example="retail",
     *                     description="Type of business (e.g., retail, restaurant, service)"
     *                 ),
     *                 @OA\Property(
     *                     property="business_address",
     *                     type="string",
     *                     maxLength=500,
     *                     example="123 Business St, City, Country",
     *                     description="Complete business address"
     *                 ),
     *                 @OA\Property(
     *                     property="lat",
     *                     type="number",
     *                     format="float",
     *                     example=12.345678,
     *                     description="Latitude of business location"
     *                 ),
     *                 @OA\Property(
     *                     property="long",
     *                     type="number",
     *                     format="float",
     *                     example=98.765432,
     *                     description="Longitude of business location"
     *                 ),
     *                 @OA\Property(
     *                     property="temp_merchant_code",
     *                     type="string",
     *                     example="TEMP_123456",
     *                     description="Temporary merchant code for registration (must start with TEMP_)"
     *                 ),
     *                 @OA\Property(
     *                     property="company_logo",
     *                     type="string",
     *                     format="binary",
     *                     description="Company logo image (JPEG, PNG, JPG, GIF - max 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="trade_license",
     *                     type="string",
     *                     format="binary",
     *                     description="Trade license document (PDF, JPEG, PNG, JPG - max 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="tax_certification",
     *                     type="string",
     *                     format="binary",
     *                     description="Tax certification document (PDF, JPEG, PNG, JPG - max 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="user_id_document",
     *                     type="string",
     *                     format="binary",
     *                     description="User ID document (PDF, JPEG, PNG, JPG - max 5MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Merchant registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Merchant registered successfully"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="merchant",
     *                     type="object",
     *                     ref="#/components/schemas/MerchantResource"
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     ref="#/components/schemas/UserResource"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The first name field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The last name field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="This email is already registered.")
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="array",
     *                     @OA\Items(type="string", example="The phone field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="business_name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The business name field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="owner_name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The owner name field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="business_type",
     *                     type="array",
     *                     @OA\Items(type="string", example="Please select a business type.")
     *                 ),
     *                 @OA\Property(
     *                     property="business_address",
     *                     type="array",
     *                     @OA\Items(type="string", example="The business address field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="temp_merchant_code",
     *                     type="array",
     *                     @OA\Items(type="string", example="The temp merchant code field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="company_logo",
     *                     type="array",
     *                     @OA\Items(type="string", example="The company logo must be an image file.")
     *                 ),
     *                 @OA\Property(
     *                     property="trade_license",
     *                     type="array",
     *                     @OA\Items(type="string", example="The trade license must be a PDF, JPEG, PNG, or JPG file.")
     *                 ),
     *                 @OA\Property(
     *                     property="tax_certification",
     *                     type="array",
     *                     @OA\Items(type="string", example="The tax certification must be a PDF, JPEG, PNG, or JPG file.")
     *                 ),
     *                 @OA\Property(
     *                     property="user_id_document",
     *                     type="array",
     *                     @OA\Items(type="string", example="The user ID document must be a PDF, JPEG, PNG, or JPG file.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Failed to register merchant"
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Internal server error message"
     *             )
     *         )
     *     )
     * )
     */
    public function register(MerchantRegistrationRequest $request): JsonResponse
    {
        try {
            // Get validated data
            // $data = $request->validated();

            // Handle file uploads
            $result = $this->merchantRepository->registerMerchant($request);

            return response()->json([
                'status' => 'success',
                'message' => 'Merchant registered successfully',
                'data' => [
                    'merchant' => $result['merchant'],
                    'user' => $result['user']
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to register merchant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}