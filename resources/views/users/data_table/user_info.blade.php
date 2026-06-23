<div class="d-flex align-items-center">
    <!--begin::Avatar-->
    <div class="symbol symbol-45px me-5">
        @if($item->profile_image)
            <img src="{{ asset('storage/' . $item->profile_image) }}" alt="{{ $item->name }}" class="rounded-circle" />
        @else
            <div class="symbol-label bg-light-primary text-primary fs-6 fw-bolder">
                {{ strtoupper(substr($item->name, 0, 1)) }}
            </div>
        @endif
    </div>
    <!--end::Avatar-->
    
    <!--begin::User info-->
    <div class="d-flex flex-column">
        <a href="{{ route('users.show', $item->id) }}" class="text-gray-800 text-hover-primary mb-1 fw-bold">
            {{ $item->name }}
        </a>
        <span class="text-gray-500 fw-semibold d-block fs-7">
            {{ $item->email }}
        </span>
    </div>
    <!--end::User info-->
</div>
