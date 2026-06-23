<?php

namespace App\Services;

use App\Models\ChangeHistory;
use App\Models\ChangeRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChangeRequestService
{
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

            $payload = $request->payload ?? [];

            // Build before/after snapshots limited to the keys present in the payload
            $targetKeys = array_keys($payload);
            $beforeSnapshot = Arr::only($changeable->getAttributes(), $targetKeys);
            // dd($beforeSnapshot);
            // Apply payload
            foreach ($payload as $field => $value) {
                if($field == 'tax_certified_number'){
                    $changeable->setAttribute('tax_number', $value);
                    continue;
                }
                $changeable->setAttribute($field, $value);
            }
            $changeable->save();

            $afterSnapshot = Arr::only($changeable->fresh()->getAttributes(), $targetKeys);

            // dd($afterSnapshot,$beforeSnapshot);
            // History
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

            // Update request status
            $request->status = 'approved';
            $request->approved_at = now();
            $request->approved_id = method_exists($approver, 'getKey') ? $approver->getKey() : null;
            $request->moderation_note = $moderationNote;
            $request->approver()->associate($approver);
            $request->save();

            $request->changeable->status = 'pending';
            $request->changeable->save();

            return $history;
        });
    }

    /**
     * Reject a change request; does not alter the target model.
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

        $request->changeable->status = 'pending';
        $request->changeable->save();

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
}


