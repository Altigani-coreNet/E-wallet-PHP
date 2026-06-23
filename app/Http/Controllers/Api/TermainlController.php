<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TerminalService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Models\Terminal; // Added this import for Terminal model
use Illuminate\Support\Facades\Log;

class TermainlController extends Controller
{
    use ApiResponse;

    protected $terminalService;

    public function __construct(TerminalService $terminalService)
    {
        $this->terminalService = $terminalService;
    }

    /**
     * Register or retrieve terminal by device information
     * 
     * @OA\Post(
     *     path="/terminals/register-device",
     *     operationId="registerOrRetrieveTerminal",
     *     tags={"Terminals"},
     *     summary="Register new terminal or retrieve existing terminal by device ID",
     *     description="This endpoint allows devices to register themselves or retrieve existing terminal information based on device_id. If the device_id doesn't exist, a new terminal will be created. If it exists, the existing terminal_id will be returned.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_id"},
     *             @OA\Property(
     *                 property="device_id",
     *                 type="string",
     *                 description="Unique device identifier",
     *                 example="DEVICE_123456789"
     *             ),
     *             @OA\Property(
     *                 property="brand",
     *                 type="string",
     *                 description="Terminal brand",
     *                 example="Verifone",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="model",
     *                 type="string",
     *                 description="Terminal model",
     *                 example="VX520",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="manufacturer",
     *                 type="string",
     *                 description="Terminal manufacturer",
     *                 example="Verifone",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="serial_no",
     *                 type="string",
     *                 description="Serial number",
     *                 example="SN123456789",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="sdk_id",
     *                 type="string",
     *                 description="SDK ID",
     *                 example="SDK001",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="sdk_version",
     *                 type="string",
     *                 description="SDK Version",
     *                 example="1.0.0",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="android_os",
     *                 type="string",
     *                 description="Android OS version",
     *                 example="Android 11",
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success - Terminal registered or retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Terminal registered successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="terminal_id", type="string", example="TERM12345678"),
     *                 @OA\Property(property="is_new", type="boolean", example=true),
     *                 @OA\Property(property="message", type="string", example="Terminal registered successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Validation error or business logic error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Device ID is required"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to process terminal registration: Database connection error"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function registerOrRetrieveTerminal(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $request->validate([
                'device_id' => 'required|string',
                'brand' => 'nullable|string',
                'model' => 'nullable|string',
                'manufacturer' => 'nullable|string',
                'serial_no' => 'nullable|string',
                'sdk_id' => 'nullable|string',
                'sdk_version' => 'nullable|string',
                'android_os' => 'nullable|string',
            ]);

            $deviceData = $request->only([
                'device_id',
                'brand',
                'model',
                'manufacturer',
                'serial_no',
                'sdk_id',
                'sdk_version',
                'android_os'
            ]);

            // dd($deviceData);

            // Call service method
            $result = $this->terminalService->registerOrRetrieveTerminal($deviceData);

            if ($result['success']) {
                return $this->SuccessMessage([
                    'terminal_id' => $result['terminal_id'],
                    'is_new' => $result['is_new'],
                    'message' => $result['message']
                ]);
                } else {
                    return $this->ErrorMessage($result['message'], null, 400);
                }

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to process terminal registration: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Link terminal to authenticated user
     * 
     * @OA\Post(
     *     path="/api/terminals/link-terminal",
     *     summary="Link terminal to authenticated user",
     *     description="Links a terminal to the currently authenticated user using an activation code",
     *     tags={"Terminals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"activation_code","terminal_id"},
     *             @OA\Property(
     *                 property="activation_code",
     *                 type="string",
     *                 description="Activation code to authorize terminal linking",
     *                 example="00000000",
     *                 minLength=8,
     *                 maxLength=8
     *             ),
     *             @OA\Property(
     *                 property="terminal_id",
     *                 type="string",
     *                 description="Terminal identifier to link",
     *                 example="TERM12345678"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success - Terminal linked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Terminal linked successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="terminal_id", type="string", example="TERM12345678"),
     *                 @OA\Property(property="terminal_internal_id", type="integer", example=123),
     *                 @OA\Property(property="user_id", type="integer", example=456)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Invalid activation code",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You entered invalid numbers"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Terminal not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Terminal not found"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error - Invalid request data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="activation_code",
     *                     type="array",
     *                     @OA\Items(type="string", example="The activation code field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="terminal_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The terminal id field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to link terminal: Database connection error"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function linkTerminal(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $request->validate([
                'activation_code' => 'required|string',
                'terminal_id' => 'required|string'
            ]);

            $activationCode = $request->input('activation_code');
            $terminalId = $request->input('terminal_id');

            // Check if activation code is '000000000'
            if ($activationCode !== '000000000') {
                return $this->ErrorMessage('You entered invalid numbers', null, 400);
            }

            // Get the authenticated user
            $user = $request->user();
            
            if (!$user) {
                return $this->ErrorMessage('User not authenticated', null, 401);
            }

            // Find terminal by terminal_id
            $terminal = Terminal::where('terminal_id', $terminalId)->first();
            
            if (!$terminal) {
                return $this->ErrorMessage('Terminal not found', null, 404);
            }

            // Check if terminal is already active for another user
            if ($terminal->termainl_status === 'online') {
                return $this->ErrorMessage('Terminal is already active for another user', null, 409);
            }

            // Sync terminal with the current user
            $user->terminals()->sync([$terminal->id]);

            // Also update the terminals JSON field in users table
            $user->addTerminalIds([$terminal->id]);

            // Set this terminal as the current terminal for the user
            $user->current_terminal_id = $terminal->id;
            $user->save();

            // Store old status for logging
            $oldStatus = $terminal->terminal_status;
            
            // Make terminal active and set status to online
            $terminal->is_active = true;
            $terminal->terminal_status = 'online';
            $terminal->save();
            
            // Log the terminal activation with user information
            
            // Log status change if status actually changed
            if ($oldStatus !== 'online') {
                // $terminal->logStatusChange($oldStatus, 'online', $user);
                $terminal->logActivity('activated', $user, 'Terminal activated and linked to user');
            
            }
            
            // Log user assignment to terminal
            $terminal->logUserAssignment($user, $user);

            // Log the terminal linking
            Log::info("Terminal linked and activated", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'terminal_id' => $terminalId,
                'terminal_internal_id' => $terminal->id,
                'action' => 'link_and_activate'
            ]); 

            return $this->SuccessMessage([
                'message' => 'Terminal linked and activated successfully',
                'terminal_id' => $terminalId,
                'terminal_internal_id' => $terminal->id,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to link terminal: ' . $e->getMessage(), null, 500);
        }
    }
}
