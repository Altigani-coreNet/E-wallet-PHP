<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookEvent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    use ApiResponse;

    /**
     * Get all webhooks for the authenticated merchant
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId) {
                return $this->ErrorMessage('Merchant not found', null, 404);
            }

            $webhooks = Webhook::where('merchant_id', $merchantId)
                ->with(['events:id,name,category,description'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Add event count to each webhook
            $webhooks->each(function ($webhook) {
                $webhook->events_count = $webhook->events->count();
            });

            return $this->SuccessMessage([
                'webhooks' => $webhooks,
                'total' => $webhooks->count(),
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to retrieve webhooks: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get available webhook events (grouped by category)
     */
    public function getAvailableEvents()
    {
        try {
            $events = WebhookEvent::groupedByCategory();

            // Format the response
            $formattedEvents = [];
            foreach ($events as $category => $categoryEvents) {
                $formattedEvents[] = [
                    'category' => $category,
                    'version' => $categoryEvents->first()->version,
                    'events_count' => $categoryEvents->count(),
                    'events' => $categoryEvents->map(function ($event) {
                        return [
                            'id' => $event->id,
                            'name' => $event->name,
                            'description' => $event->description,
                            'version' => $event->version,
                        ];
                    }),
                ];
            }

            return $this->SuccessMessage([
                'categories' => $formattedEvents,
                'total_events' => WebhookEvent::active()->count(),
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to retrieve events: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new webhook
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId) {
                return $this->ErrorMessage('Merchant not found', null, 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'endpoint_url' => 'required|url|max:500',
                'event_ids' => 'required|array|min:1',
                'event_ids.*' => 'exists:webhook_events,id',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->ErrorMessage('Validation error', $validator->errors(), 422);
            }

            // Create webhook
            $webhook = Webhook::create([
                'merchant_id' => $merchantId,
                'name' => $request->name,
                'description' => $request->description,
                'endpoint_url' => $request->endpoint_url,
                'is_active' => $request->is_active ?? true,
            ]);

            // Attach selected events
            $webhook->events()->attach($request->event_ids);

            // Load relationships
            $webhook->load('events:id,name,category,description');
            $webhook->events_count = $webhook->events->count();

            return $this->SuccessMessage([
                'message' => 'Webhook created successfully',
                'webhook' => $webhook,
            ], 201);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create webhook: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get a single webhook
     */
    public function show($id)
    {
        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId) {
                return $this->ErrorMessage('Merchant not found', null, 404);
            }

            $webhook = Webhook::where('id', $id)
                ->where('merchant_id', $merchantId)
                ->with(['events:id,name,category,description,version'])
                ->first();

            if (!$webhook) {
                return $this->ErrorMessage('Webhook not found', null, 404);
            }

            $webhook->events_count = $webhook->events->count();
            $webhook->recent_logs = $webhook->recentLogs(10);

            return $this->SuccessMessage(['webhook' => $webhook]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to retrieve webhook: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update a webhook
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId) {
                return $this->ErrorMessage('Merchant not found', null, 404);
            }

            $webhook = Webhook::where('id', $id)
                ->where('merchant_id', $merchantId)
                ->first();

            if (!$webhook) {
                return $this->ErrorMessage('Webhook not found', null, 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'endpoint_url' => 'sometimes|url|max:500',
                'event_ids' => 'sometimes|array|min:1',
                'event_ids.*' => 'exists:webhook_events,id',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->ErrorMessage('Validation error', $validator->errors(), 422);
            }

            // Update webhook
            $webhook->update($request->only(['name', 'description', 'endpoint_url', 'is_active']));

            // Update events if provided
            if ($request->has('event_ids')) {
                $webhook->events()->sync($request->event_ids);
            }

            // Load relationships
            $webhook->load('events:id,name,category,description');
            $webhook->events_count = $webhook->events->count();

            return $this->SuccessMessage([
                'message' => 'Webhook updated successfully',
                'webhook' => $webhook,
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update webhook: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete a webhook
     */
    public function destroy($id)
    {
        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId) {
                return $this->ErrorMessage('Merchant not found', null, 404);
            }

            $webhook = Webhook::where('id', $id)
                ->where('merchant_id', $merchantId)
                ->first();

            if (!$webhook) {
                return $this->ErrorMessage('Webhook not found', null, 404);
            }

            $webhook->delete();

            return $this->SuccessMessage(['message' => 'Webhook deleted successfully']);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete webhook: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Toggle webhook active status
     */
    public function toggle($id)
    {
        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId) {
                return $this->ErrorMessage('Merchant not found', null, 404);
            }

            $webhook = Webhook::where('id', $id)
                ->where('merchant_id', $merchantId)
                ->first();

            if (!$webhook) {
                return $this->ErrorMessage('Webhook not found', null, 404);
            }

            $webhook->is_active = !$webhook->is_active;
            $webhook->save();

            return $this->SuccessMessage([
                'message' => 'Webhook status updated successfully',
                'webhook' => $webhook,
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to toggle webhook: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get webhook logs
     */
    public function logs($id, Request $request)
    {
        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId) {
                return $this->ErrorMessage('Merchant not found', null, 404);
            }

            $webhook = Webhook::where('id', $id)
                ->where('merchant_id', $merchantId)
                ->first();

            if (!$webhook) {
                return $this->ErrorMessage('Webhook not found', null, 404);
            }

            $perPage = $request->get('per_page', 20);
            $status = $request->get('status'); // success, failed

            $query = $webhook->logs()->with('webhookEvent:id,name,category');

            if ($status) {
                $query->where('status', $status);
            }

            $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->SuccessMessage([
                'logs' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'per_page' => $logs->perPage(),
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to retrieve logs: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Regenerate webhook secret
     */
    public function regenerateSecret($id)
    {
        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;

            if (!$merchantId) {
                return $this->ErrorMessage('Merchant not found', null, 404);
            }

            $webhook = Webhook::where('id', $id)
                ->where('merchant_id', $merchantId)
                ->first();

            if (!$webhook) {
                return $this->ErrorMessage('Webhook not found', null, 404);
            }

            $webhook->secret = 'whsec_' . \Illuminate\Support\Str::random(32);
            $webhook->save();

            return $this->SuccessMessage([
                'message' => 'Webhook secret regenerated successfully',
                'secret' => $webhook->secret,
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to regenerate secret: ' . $e->getMessage(), null, 500);
        }
    }
}

