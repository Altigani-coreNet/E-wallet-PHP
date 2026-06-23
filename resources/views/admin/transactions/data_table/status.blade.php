@php
    $badgeClass = 'badge-light';
    $icon = '';
    
    // Use state field if available, otherwise use status field
    $status = strtoupper($item->state ?? $item->status ?? 'UNKNOWN');
    
    switch($status) {
        case 'APPROVED':
        case 'CAPTURED':
        case 'PROCESSED':
        case 'SUCCESS':
        case 'COMPLETED':
            $badgeClass = 'badge-light-success';
            $icon = '<i class="ki-duotone ki-check-circle fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'PENDING':
        case 'PROCESSING':
        case 'AUTHORIZED':
            $badgeClass = 'badge-light-warning';
            $icon = '<i class="ki-duotone ki-time fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'DECLINED':
        case 'FAILED':
        case 'REJECTED':
        case 'ERROR':
            $badgeClass = 'badge-light-danger';
            $icon = '<i class="ki-duotone ki-cross-circle fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'REFUNDED':
        case 'REFUND':
            $badgeClass = 'badge-light-info';
            $icon = '<i class="ki-duotone ki-arrow-left fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'VOIDED':
        case 'VOID':
        case 'CANCELLED':
        case 'CANCELED':
            $badgeClass = 'badge-light-secondary';
            $icon = '<i class="ki-duotone ki-cross fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'EXPIRED':
            $badgeClass = 'badge-light-dark';
            $icon = '<i class="ki-duotone ki-clock fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'REVERSED':
            $badgeClass = 'badge-light-danger';
            $icon = '<i class="ki-duotone ki-arrows-circle fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        default:
            $badgeClass = 'badge-light-secondary';
            $icon = '<i class="ki-duotone ki-question fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
    }
    
    $displayText = ucfirst(strtolower(str_replace('_', ' ', $status)));
@endphp

<span class="badge {{ $badgeClass }} fs-7 fw-semibold">
    {!! $icon !!}
    {{ $displayText }}
</span>
