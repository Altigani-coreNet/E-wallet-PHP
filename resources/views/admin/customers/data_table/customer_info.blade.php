<div class="d-flex align-items-center">
    <div class="symbol symbol-35px symbol-circle me-3">
        <span class="symbol-label fw-bold bg-light-primary text-primary">
            {{ strtoupper(mb_substr($customer->name ?? '', 0, 1)) ?: 'C' }}
        </span>
    </div>
    <div class="d-flex flex-column">
        <span class="fw-bold text-gray-800">{{ $customer->name ?? '' }}</span>
        <span class="text-muted fs-7">{{ $customer->email ?? '' }}</span>
        <span class="text-muted fs-7">{{ $customer->phone ?? '' }}</span>
    </div>
</div>

