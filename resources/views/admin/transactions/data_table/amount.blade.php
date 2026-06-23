@php
    $statusColors = [
        'completed' => 'text-success',
        'pending' => 'text-warning',
        'failed' => 'text-danger',
        'cancelled' => 'text-muted'
    ];
    
    $color = $statusColors[$state] ?? 'text-gray-800';
    $currencyCode = $currency ? ($currency->currency_code ?? 'USD') : 'USD';
@endphp

<div class="d-flex flex-column">
    <span class="fw-bold {{ $color }} fs-6">{{ number_format($amount, 2) }} {{ $currencyCode }}</span>
    <span class="text-muted fs-7">{{ ucfirst($status) }}</span>
</div>
