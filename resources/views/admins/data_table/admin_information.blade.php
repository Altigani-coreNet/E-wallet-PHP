<div class="d-flex align-items-center">
    <div class="symbol symbol-40px me-3">
        {!! $admin->getTableImage() !!}
    </div>
    <div class="d-flex flex-column">
        <span class="fw-bold">{{ $admin->name }}</span>
        <span class="text-muted small">{{ $admin->email }}</span>
        @if($admin->phone)
            <span class="text-muted small">{{ $admin->phone }}</span>
        @endif
    </div>
</div>

