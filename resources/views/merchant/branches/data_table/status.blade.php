@php
    $isPending = $branch->status === 'pending';
    $badgeClass = $isPending ? 'secondary' : ($branch->is_active ? 'success' : 'danger');
    $label = $isPending ? 'Waiting for approval' : ($branch->is_active ? 'Active' : 'Inactive');
@endphp
<div class="badge badge-light-{{ $badgeClass }} fw-bold status-toggle"
     data-id="{{ $branch->id }}" data-status="{{ $branch->status }}" style="cursor: pointer;">
    {{ $label }}
</div>