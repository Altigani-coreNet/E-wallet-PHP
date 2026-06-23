@php
    $methodColors = [
        'credit_card' => 'badge-light-primary',
        'debit_card' => 'badge-light-success',
        'cash' => 'badge-light-warning',
        'bank_transfer' => 'badge-light-info',
        'mobile_payment' => 'badge-light-dark'
    ];
    
    $methodLabels = [
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'mobile_payment' => 'Mobile Payment'
    ];
    
    $color = $methodColors[$method] ?? 'badge-light-secondary';
    $label = $methodLabels[$method] ?? ucfirst(str_replace('_', ' ', $method));
@endphp

<span class="badge {{ $color }} fw-bold">{{ $label }}</span>
