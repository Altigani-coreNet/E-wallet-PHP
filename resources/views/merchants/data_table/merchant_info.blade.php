<div class="d-flex flex-column">
    <div class="fw-bold fs-6 text-gray-800 mb-1">
        {{ $merchant->name }}
    </div>
    @if($merchant->owner_name)
        <div class="fs-7 text-gray-500">
            Owner: {{ $merchant->owner_name }}
        </div>
    @endif
</div>
