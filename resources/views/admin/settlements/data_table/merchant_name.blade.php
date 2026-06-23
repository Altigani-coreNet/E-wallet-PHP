@if($settlement->merchant)
    <span class="text-dark fw-bold text-hover-primary fs-6">
        {{ $settlement->merchant->name }}
    </span>
@else
    <span class="text-muted">N/A</span>
@endif
