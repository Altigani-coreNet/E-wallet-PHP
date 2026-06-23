@if($settlement->batch)
    <a href="{{ route('admin.batches.show', $settlement->batch) }}" class="text-dark fw-bold text-hover-primary fs-6">
        {{ $settlement->batch->batch_number }}
    </a>
@else
    <span class="text-muted">N/A</span>
@endif
