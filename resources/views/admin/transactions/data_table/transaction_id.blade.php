<div class="d-flex flex-column">
    <span class="fw-bold text-gray-800 fs-6"> <a href="{{ route('admin.transactions.show', $id) }}">{{ $transaction_id }}</a></span>
    <span class="text-muted fs-7">{{ $invoice_no ?? 'N/A' }}</span>
</div>
