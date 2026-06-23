<span class="text-dark fw-bold text-hover-primary mb-1 fs-6">
    {{ $transaction->created_at ? \Carbon\Carbon::parse($transaction->created_at)->format('M d, Y') : 'N/A' }}
</span>
<div class="text-muted fs-7">{{ $transaction->created_at ? \Carbon\Carbon::parse($transaction->created_at)->format('H:i:s') : '' }}</div> 