<div class="d-flex flex-column">
    <span class="fw-bold text-gray-800 fs-6">{{ $transaction_datetime ? \Carbon\Carbon::parse($transaction_datetime)->format('M d, Y') : 'N/A' }}</span>
    <span class="text-muted fs-7">{{ $transaction_datetime ? \Carbon\Carbon::parse($transaction_datetime)->format('h:i A') : 'N/A' }}</span>
</div>
