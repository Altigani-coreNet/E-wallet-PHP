<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : [];
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        $groupId = $meta['notification_group_id'] ?? ($data['notification_group_id'] ?? $this->id);
        $code = $meta['notification_code'] ?? ($data['notification_code'] ?? ('NTF-' . strtoupper(substr((string) $groupId, 0, 8))));

        return [
            'id' => $this->id,
            'code' => $code,
            'title' => $this->title ?? ($data['title'] ?? null),
            'description' => $this->description ?? ($data['body'] ?? null),
            'link_id' => $data['link_id'] ?? null,
            'topic' => $this->topic ?? ($meta['topic'] ?? ($data['topic'] ?? null)),
            'target_type' => $this->target_type ?? ($meta['target_type'] ?? ($data['target_type'] ?? null)),
            'merchant_id' => $this->merchant_id ?? ($meta['merchant_id'] ?? ($data['merchant_id'] ?? null)),
            'image' => $this->image ?? ($meta['image'] ?? null),
            'is_admin' => (bool) ($this->is_admin ?? ($meta['is_admin'] ?? false)),
            'is_read' => !is_null($this->read_at),
            'read_at' => $this->read_at,
            'sent_at' => $data['sent_at'] ?? $this->created_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

