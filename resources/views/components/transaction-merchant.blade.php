<span class="text-dark fw-bold text-hover-primary mb-1 fs-6">
    {{ $transaction->merchant ? $transaction->merchant->name : 'N/A' }}
</span>
<div class="text-muted fs-7">MID: {{ $transaction->mid ?? 'N/A' }}</div> 