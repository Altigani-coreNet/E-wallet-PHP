@if($transaction->method == 'VISA')
    <span class="badge badge-light-primary">VISA</span>
@elseif($transaction->method == 'MASTERCARD')
    <span class="badge badge-light-warning">MASTERCARD</span>
@elseif($transaction->method == 'AMEX')
    <span class="badge badge-light-info">AMEX</span>
@elseif($transaction->method == 'DISCOVER')
    <span class="badge badge-light-success">DISCOVER</span>
@else
    <span class="badge badge-light-secondary">{{ $transaction->method ?? 'N/A' }}</span>
@endif 