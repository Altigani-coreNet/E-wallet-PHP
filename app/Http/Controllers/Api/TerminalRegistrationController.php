<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TerminalService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Models\Terminal;
use Illuminate\Support\Facades\Log;

class TerminalRegistrationController extends Controller
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
            if ($terminal->terminal_status === 'online') {
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

