@php
    $badgeClass = 'badge-light';
    $icon = '';
    
    switch(strtolower($item->payment_type ?? $item->method ?? '')) {
        case 'card':
        case 'credit_card':
        case 'debit_card':
            $badgeClass = 'badge-light-success';
            $icon = '<i class="ki-duotone ki-credit-cart fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'web':
        case 'online':
        case 'internet':
            $badgeClass = 'badge-light-info';
            $icon = '<i class="ki-duotone ki-global fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'bank':
        case 'bank_transfer':
        case 'wire_transfer':
            $badgeClass = 'badge-light-primary';
            $icon = '<i class="ki-duotone ki-bank fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'mobile':
        case 'mobile_wallet':
        case 'mobile_payment':
            $badgeClass = 'badge-light-warning';
            $icon = '<i class="ki-duotone ki-smartphone fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'qr':
        case 'qr_code':
        case 'qrcode':
            $badgeClass = 'badge-light-secondary';
            $icon = '<i class="ki-duotone ki-qr-code fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        case 'cash':
            $badgeClass = 'badge-light-dark';
            $icon = '<i class="ki-duotone ki-money fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
        default:
            $badgeClass = 'badge-light-secondary';
            $icon = '<i class="ki-duotone ki-abstract-26 fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>';
            break;
    }
    
    $displayText = ucfirst(str_replace('_', ' ', $item->payment_type ?? $item->method ?? 'Unknown'));
@endphp

<span class="badge {{ $badgeClass }} fs-7 fw-semibold">
    {!! $icon !!}
    {{ $displayText }}
</span>
