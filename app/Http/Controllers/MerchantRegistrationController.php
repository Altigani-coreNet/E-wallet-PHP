<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantRegistrationRequest;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Plan;
use App\Models\Category;
use App\Models\Attachments;
use App\Services\MerchantService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Support\Str;
use App\Traits\HandlesMerchantFiles;
use Illuminate\Support\Facades\Mail;
use App\Mail\MerchantRegistrationConfirmationMail;

class MerchantRegistrationController extends Controller
{
    use HandlesMerchantFiles;
    
    protected $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * Show the merchant registration form
     */
    public function showRegistrationForm()
    {
        $plans = Plan::with('features')->get();
        $categories = Category::get();
        
        return view('auth.merchant-register', compact('plans', 'categories'));
    }

    /**
     * Process the merchant registration
     */
    public function register(MerchantRegistrationRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'name' => $request->validated('first_name') . ' ' . $request->validated('last_name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => $request->password   ?? Hash::make(Str::random(12)), // Generate random password
                'is_active' => false, // Will be activated after admin approval
            ]);

            // Get merchant code from request (generated on frontend)
            $merchantCode = $request->validated('temp_merchant_code');
            if (!$merchantCode) {
                throw new \Exception('Merchant code is required');
            }

            // Generate merchant code
            $generatedMerchantCode = Merchant::generateMerchantCode();

            // Create merchant profile using existing structure
            $merchant = Merchant::create([
                'name' => $request->validated('business_name'),
                'owner_name' => $request->validated('owner_name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'user_id' => $user->id,
                'business_type' => $request->validated('business_type'),
                'status' => 'pending',
                'merchant_code' => $generatedMerchantCode,
                'is_active' => false,
                'address' => $request->validated('business_address'),
                'latitude' => $request->safe()->has('lat') ? $request->validated('lat') : null,
                'longitude' => $request->safe()->has('long') ? $request->validated('long') : null,
                'add_type' => 'website',
            ]);

            $user->merchant_id = $merchant->id;
            $user->save();

            // Create registration log with merchant code
            $merchant->logs()->create([
                'action' => 'created',
                'new_values' => $merchant->toArray(),
                'metadata' => [
                    'message' => "New merchant registered with code: {$generatedMerchantCode}",
                    'registration_source' => 'website',
                    'temp_merchant_code' => $merchantCode,
                ],
                'user_id' => $user->id,
                'user_type' => get_class($user)
            ]);

          

            
            // Handle cached file uploads
            $this->handleCachedFileUploads($merchantCode, $merchant);

            // Send registration confirmation email
            Mail::to($user->email)->send(new MerchantRegistrationConfirmationMail($user, $merchant));

            DB::commit();

            // Check if request wants JSON response (AJAX request)
            if (RequestFacade::wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Registration submitted successfully! Please wait for admin approval.',
                    'merchant_id' => $merchant->id
                ], Response::HTTP_CREATED);
            }

            return redirect()->route('merchant.registration.success')
                ->with('success', 'Registration submitted successfully! Please wait for admin approval.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Check if request wants JSON response (AJAX request)
            if (RequestFacade::wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed. Please try again. Error: ' . $e->getMessage()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }

    /**
     * Handle cached file uploads for merchant documents
     * Files are uploaded separately and cached, then linked here
     */
    protected function handleCachedFileUploads(string $merchantCode, Merchant $merchant)
    {
        try {
            $this->attachCachedFilesToMerchant($merchant, $merchantCode);
        } catch (\Exception $e) {
            LaravelLog::error("Error handling cached files for merchant {$merchantCode}: " . $e->getMessage());
            // Don't throw exception - files are optional
        }
    }

    /**
     * Handle file uploads for merchant documents (legacy method)
     * Note: Files are optional - this method handles cases where files may or may not be present
     */
    protected function handleFileUploads(Request $request, Merchant $merchant)
    {
        $uploadPath = 'merchants/' . $merchant->id . '/documents/';
        $fullUploadPath = public_path($uploadPath);

        // Create directory if it doesn't exist
        if (!file_exists($fullUploadPath)) {
            mkdir($fullUploadPath, 0755, true);
        }

        // Handle company logo
        if ($request->hasFile('company_logo')) {
            $logoFile = $request->file('company_logo');
            $logoFilename = Str::random(40) . '.' . $logoFile->getClientOriginalExtension();
            $logoPath = $uploadPath . 'logo/' . $logoFilename;
            
            // Create logo subdirectory
            $logoDir = public_path($uploadPath . 'logo');
            if (!file_exists($logoDir)) {
                mkdir($logoDir, 0755, true);
            }
            
            $logoFile->move($logoDir, $logoFilename);
            $merchant->update(['logo' => $logoPath]);
        }

        // Handle trade license
        if ($request->hasFile('trade_license')) {
            $tradeLicenseFile = $request->file('trade_license');
            $tradeLicenseFilename = Str::random(40) . '.' . $tradeLicenseFile->getClientOriginalExtension();
            $tradeLicensePath = $uploadPath . 'documents/' . $tradeLicenseFilename;
            
            // Create documents subdirectory
            $documentsDir = public_path($uploadPath . 'documents');
            if (!file_exists($documentsDir)) {
                mkdir($documentsDir, 0755, true);
            }
            
            $tradeLicenseFile->move($documentsDir, $tradeLicenseFilename);
            $merchant->attachments()->create([
                'file_path' => $tradeLicensePath,
                'file_type' => 'trade_license',
                'file_name' => 'Trade License'
            ]);
        }

        // Handle tax certification
        if ($request->hasFile('tax_certification')) {
            $taxCertFile = $request->file('tax_certification');
            $taxCertFilename = Str::random(40) . '.' . $taxCertFile->getClientOriginalExtension();
            $taxCertPath = $uploadPath . 'documents/' . $taxCertFilename;
            
            $taxCertFile->move(public_path($uploadPath . 'documents'), $taxCertFilename);
            $merchant->attachments()->create([
                'file_path' => $taxCertPath,
                'file_type' => 'tax_certification',
                'file_name' => 'Tax Certification'
            ]);
        }

        // Handle user ID document
        if ($request->hasFile('user_id_document')) {
            $userIdFile = $request->file('user_id_document');
            $userIdFilename = Str::random(40) . '.' . $userIdFile->getClientOriginalExtension();
            $userIdPath = $uploadPath . 'documents/' . $userIdFilename;
            
            $userIdFile->move(public_path($uploadPath . 'documents'), $userIdFilename);
            $merchant->attachments()->create([
                'file_path' => $userIdPath,
                'file_type' => 'user_id_document',
                'file_name' => 'User ID Document'
            ]);
        }
    }

    /**
     * Show registration success page
     */
    public function showSuccess()
    {
        return view('auth.merchant-registration-success');
    }

    /**
     * Get cities for select dropdown
     */
    public function getCities(Request $request)
    {
        $search = $request->get('search');
        // TODO: Implement city search logic
        $cities = []; // Replace with actual city data
        
        return response()->json($cities);
    }

    /**
     * Get countries for select dropdown
     */
    public function getCountries(Request $request)
    {
        $search = $request->get('search');
        // TODO: Implement country search logic
        $countries = []; // Replace with actual country data
        
        return response()->json($countries);
    }

    /**
     * Get city IDs for selected cities
     */
    public function getCityIds(Request $request)
    {
        $ids = $request->get('ids');
        // TODO: Implement city ID retrieval logic
        $cities = []; // Replace with actual city data
        
        return response()->json($cities);
    }

    /**
     * Get country IDs for selected countries
     */
    public function getCountryIds(Request $request)
    {
        $ids = $request->get('ids');
        // TODO: Implement country ID retrieval logic
        $countries = []; // Replace with actual country data
        
        return response()->json($countries);
    }
}
