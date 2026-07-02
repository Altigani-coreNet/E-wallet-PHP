<?php

namespace App\Services;

use App\Models\ChangeHistory;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Merchant;
use App\Modules\CustomerAuth\Notifications\CustomerNotificationType;
use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
use App\Modules\CustomerAuth\Services\CustomerSystemNotificationService;
use App\Support\CustomerEventMessageBuilder;
use App\Modules\AdminKyc\Services\AdminKycQueueService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChangeRequestService
{
    public function __construct(
        private readonly CustomerSystemNotificationService $customerSystemNotificationService,
        private readonly CustomerService $customerService,
        private readonly AdminKycQueueService $adminKycQueueService,
        private readonly CustomerAttachmentService $customerAttachmentService,
    ) {}

    /**
     * Submit a change request for any morphable model.
     *
     * @param Model $changeable Target model to change
     * @param array $payload Associative array of fields => new values (will be JSON stored)
     * @param Model|null $requester Optional requester (Admin, User, etc.)
     * @param string|null $reason Optional textual reason/explanation
     */
    public function submit(Model $changeable, array $payload, ?Model $requester = null, ?string $reason = null): ChangeRequest
    {
        if (empty($payload)) {
            throw new InvalidArgumentException('Payload cannot be empty.');
        }

        $changeRequest = new ChangeRequest([
            'payload' => $payload,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $changeRequest->changeable()->associate($changeable);

        if ($requester) {
            $changeRequest->requester()->associate($requester);
        }

        $changeRequest->save();

        return $changeRequest;
    }

    /**
     * Approve a change request and apply changes to the underlying model.
     * Creates a history record with before/after snapshots.
     */
    public function approve(ChangeRequest $request, Model $approver, ?string $moderationNote = null): ChangeHistory
    {
        if ($request->status !== 'pending') {
            throw new InvalidArgumentException('Only pending requests can be approved.');
        }

        return DB::transaction(function () use ($request, $approver, $moderationNote) {
            $changeable = $request->changeable;

            if (!$changeable instanceof Model) {
                throw new InvalidArgumentException('Invalid changeable model.');
            }

            $payload = $this->applicablePayload($request->payload ?? []);

            $targetKeys = array_keys($payload);
            $beforeSnapshot = $this->buildCustomerSnapshot($changeable, $targetKeys);

            foreach ($payload as $field => $value) {
                if ($changeable instanceof Customer && in_array($field, [
                    CustomerAttachmentService::URL_TYPE_PROFILE_IMAGE,
                    CustomerAttachmentService::URL_TYPE_PASSPORT_DOCUMENT,
                ], true)) {
                    $this->customerAttachmentService->syncAttachmentFromPath(
                        $changeable,
                        $field,
                        (string) $value,
                    );
                    continue;
                }

                if ($field === 'tax_certified_number') {
                    $changeable->setAttribute('tax_number', $value);
                    continue;
                }
                if ($changeable instanceof Customer && $field === 'email' && $value !== $changeable->email) {
                    $changeable->setAttribute('email_verified_at', null);
                }
                if ($changeable instanceof Customer && $field === 'phone' && $value !== $changeable->phone) {
                    $changeable->setAttribute('phone_verified_at', null);
                }
                $changeable->setAttribute($field, $value);
            }
            $changeable->save();

            $afterSnapshot = $this->buildCustomerSnapshot($changeable->fresh(), $targetKeys);

            $history = new ChangeHistory([
                'before' => $beforeSnapshot,
                'after' => $afterSnapshot,
                'payload' => $payload,
                'change_request_id' => $request->id,
                'user_id' => method_exists($approver, 'id') ? $approver->id : null,
            ]);

            $history->changeable()->associate($changeable);
            $history->actor()->associate($approver);
            $history->save();

            $request->status = 'approved';
            $request->approved_at = now();
            $request->approved_id = method_exists($approver, 'getKey') ? $approver->getKey() : null;
            $request->moderation_note = $moderationNote;
            $request->approver()->associate($approver);
            $request->save();

            $changeable->status = $this->resolvePostApprovalStatus($changeable, $request->payload ?? []);
            $changeable->save();

            if ($changeable instanceof Customer) {
                $this->customerSystemNotificationService->send(
                    $changeable->fresh(),
                    CustomerNotificationType::ProfileChangeRequestApproved,
                );

                $this->customerService->logCustomerEvent($changeable->fresh(), 'change_request_approved', [
                    'change_request_id' => $request->id,
                    'moderation_note' => $moderationNote,
                    'changed_fields' => array_keys($payload),
                    'event' => 'Profile change request approved',
                    'message' => CustomerEventMessageBuilder::changeRequestApproved(
                        $payload,
                        $beforeSnapshot,
                        $afterSnapshot,
                        $moderationNote,
                        method_exists($approver, 'name') ? $approver->name : null,
                    ),
                ], $beforeSnapshot, $afterSnapshot);

                $this->adminKycQueueService->broadcast();
            }

            return $history;
        });
    }

    /**
     * Reject a change request; does not alter the target model fields.
     */
    public function reject(ChangeRequest $request, Model $approver, ?string $moderationNote = null): ChangeRequest
    {
        if ($request->status !== 'pending') {
            throw new InvalidArgumentException('Only pending requests can be rejected.');
        }

        $request->status = 'rejected';
        $request->rejected_at = now();
        $request->moderation_note = $moderationNote;
        $request->approver()->associate($approver);
        $request->save();

        $changeable = $request->changeable;
        if ($changeable instanceof Model) {
            $payload = $this->applicablePayload($request->payload ?? []);

            $changeable->status = $this->resolvePostRejectionStatus($changeable, $request->payload ?? []);
            $changeable->save();

            if ($changeable instanceof Customer) {
                $this->customerSystemNotificationService->send(
                    $changeable->fresh(),
                    CustomerNotificationType::ProfileChangeRequestRejected,
                    ['note' => $moderationNote ?? ''],
                );

                $this->customerService->logCustomerEvent($changeable->fresh(), 'change_request_rejected', [
                    'change_request_id' => $request->id,
                    'moderation_note' => $moderationNote,
                    'changed_fields' => array_keys($payload),
                    'event' => 'Profile change request rejected',
                    'message' => CustomerEventMessageBuilder::changeRequestRejected(
                        $payload,
                        $moderationNote,
                        method_exists($approver, 'name') ? $approver->name : null,
                    ),
                ]);
                $this->adminKycQueueService->broadcast();
            }
        }

        return $request;
    }

    /**
     * Convenience helper to build payload from a full model input by diffing attributes.
     * Returns only changed keys.
     */
    public function diffPayload(Model $model, array $newAttributes): array
    {
        $current = $model->getAttributes();
        $payload = [];
        foreach ($newAttributes as $key => $value) {
            $currentValue = $current[$key] ?? null;
            if ($currentValue !== $value) {
                $payload[$key] = $value;
            }
        }
        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applicablePayload(array $payload): array
    {
        return Arr::except($payload, ['__meta']);
    }

    /**
     * @param  list<string>  $targetKeys
     * @return array<string, mixed>
     */
    private function buildCustomerSnapshot(Model $changeable, array $targetKeys): array
    {
        if (! $changeable instanceof Customer) {
            return Arr::only($changeable->getAttributes(), $targetKeys);
        }

        $changeable->loadMissing('attachments');
        $snapshot = [];

        foreach ($targetKeys as $key) {
            if (in_array($key, [
                CustomerAttachmentService::URL_TYPE_PROFILE_IMAGE,
                CustomerAttachmentService::URL_TYPE_PASSPORT_DOCUMENT,
            ], true)) {
                $snapshot[$key] = $changeable->attachments
                    ->firstWhere('url_type', $key)
                    ?->url ?? ($key === CustomerAttachmentService::URL_TYPE_PROFILE_IMAGE
                        ? $changeable->profile_image
                        : null);
                continue;
            }

            $snapshot[$key] = $changeable->getAttribute($key);
        }

        return $snapshot;
    }

    private function resolvePostApprovalStatus(Model $changeable, array $payload): string
    {
        if ($changeable instanceof Customer) {
            return Customer::STATUS_ACTIVE;
        }

        if ($changeable instanceof Merchant) {
            $previous = data_get($payload, '__meta.previous_status');
            if ($previous === 'requesting_updated' || $previous === 'approved') {
                return 'approved';
            }
            if (is_string($previous) && $previous !== '') {
                return $previous;
            }
        }

        return 'pending';
    }

    private function resolvePostRejectionStatus(Model $changeable, array $payload): string
    {
        $previous = data_get($payload, '__meta.previous_status');

        if ($changeable instanceof Customer) {
            return is_string($previous) && $previous !== ''
                ? $previous
                : Customer::STATUS_ACTIVE;
        }

        if ($changeable instanceof Merchant) {
            if ($previous === 'requesting_updated') {
                return 'approved';
            }
            if (is_string($previous) && $previous !== '') {
                return $previous;
            }
        }

        return 'pending';
    }
}
