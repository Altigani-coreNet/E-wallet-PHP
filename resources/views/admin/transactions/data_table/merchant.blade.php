@if($merchant)
    <div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">
            <img src="{{ $merchant->logo_url ?? asset('assets/media/avatars/blank.png') }}" alt="{{ $merchant->name }}" class="rounded-circle">
        </div>
        <div class="d-flex flex-column">
            <span class="text-gray-800 fw-bold">{{ $merchant->name }}</span>
            <span class="text-gray-500">{{ $merchant->business_type ?? 'N/A' }}</span>
        </div>
    </div>
@else
    <span class="text-muted">N/A</span>
@endif
