<span class="text-dark fw-bold text-hover-primary mb-1 fs-6">
    {{ $transaction->transaction_datetime ? \Carbon\Carbon::parse($transaction->transaction_datetime)->format('M d, Y') : 'N/A' }}
</span>
<div class="text-muted fs-7">{{ $transaction->transaction_datetime ? \Carbon\Carbon::parse($transaction->transaction_datetime)->format('H:i:s') : '' }}</div> 