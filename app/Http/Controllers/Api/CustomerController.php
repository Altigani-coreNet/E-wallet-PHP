<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\CustomerCollection;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/customers",
     *     summary="Get all customers for authenticated merchant",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CustomerResource")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Merchant not found")
     * )
     */
    public function index(Request $request)
    {
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = Customer::query();//where('merchant_id', $merchant->id);

        // Add search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->paginate($perPage);

        return new CustomerCollection($customers);
    }

    /**
     * @OA\Get(
     *     path="/api/customers/list",
     *     summary="Get simple list of customers (ID and name only) for authenticated merchant",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer list retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Merchant not found")
     * )
     */
    public function list(Request $request)
    {
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $search = $request->get('search');

        $query = Customer::where('merchant_id', $merchant->id)->select('id', 'name', 'email');

        // Add search functionality
        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $customers = $query->orderBy('name', 'asc')->get();

        return response()->json($customers);
    }

    /**
     * @OA\Get(
     *     path="/api/customers/{id}",
     *     summary="Get a specific customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/CustomerResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Unauthorized access to customer"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function show(Customer $customer)
    {
        $merchant = Auth::user()->merchant;
        
        if (!$merchant || $customer->merchant_id != $merchant->id) {
            return response()->json(['error' => 'Unauthorized access to customer'], 403);
        }

        return response()->json([
            'data' => new CustomerResource($customer)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/customers",
     *     summary="Create a new customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string", description="Customer name", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", description="Customer email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", description="Customer phone", example="+1234567890"),
     *             @OA\Property(property="address", type="string", description="Customer address", example="123 Main St"),
     *             @OA\Property(property="city", type="string", description="Customer city", example="New York"),
     *             @OA\Property(property="state", type="string", description="Customer state", example="NY"),
     *             @OA\Property(property="zip", type="string", description="Customer zip code", example="10001")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/CustomerResource")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Merchant not found")
     * )
     */
    public function store(Request $request)
    {
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
        ]);

        // Check if email already exists for this merchant
        $existingCustomer = Customer::where('merchant_id', $merchant->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existingCustomer) {
            return response()->json(['error' => 'Customer with this email already exists'], 400);
        }

        $validated['merchant_id'] = $merchant->id;
        $validated['merchant_country_id'] = $merchant->country_id;

        try {
            $customer = Customer::create($validated);
            return response()->json([
                'message' => 'Customer created successfully',
                'data' => new CustomerResource($customer)
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/customers/{id}",
     *     summary="Update a customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", description="Customer name"),
     *             @OA\Property(property="email", type="string", format="email", description="Customer email"),
     *             @OA\Property(property="phone", type="string", description="Customer phone"),
     *             @OA\Property(property="address", type="string", description="Customer address"),
     *             @OA\Property(property="city", type="string", description="Customer city"),
     *             @OA\Property(property="state", type="string", description="Customer state"),
     *             @OA\Property(property="zip", type="string", description="Customer zip code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/CustomerResource")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Unauthorized access to customer"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function update(Request $request, Customer $customer)
    {
        $merchant = Auth::user()->merchant;
        
        if (!$merchant || $customer->merchant_id != $merchant->id) {
            return response()->json(['error' => 'Unauthorized access to customer'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
        ]);

        // Check if email already exists for another customer of this merchant
        $existingCustomer = Customer::where('merchant_id', $merchant->id)
            ->where('email', $validated['email'])
            ->where('id', '!=', $customer->id)
            ->first();

        if ($existingCustomer) {
            return response()->json(['error' => 'Customer with this email already exists'], 400);
        }

        try {
            $customer->update($validated);
            return response()->json([
                'message' => 'Customer updated successfully',
                'data' => new CustomerResource($customer)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/customers/{id}",
     *     summary="Delete a customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Unauthorized access to customer"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function destroy(Customer $customer)
    {
        $merchant = Auth::user()->merchant;
        
        if (!$merchant || $customer->merchant_id != $merchant->id) {
            return response()->json(['error' => 'Unauthorized access to customer'], 403);
        }

        try {
            $customer->delete();
            return response()->json(['message' => 'Customer deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
