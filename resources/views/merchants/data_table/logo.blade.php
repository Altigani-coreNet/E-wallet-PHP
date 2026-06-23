@if($logo)
    <div class="symbol symbol-50px me-5">
        <img src="{{ asset($logo) }}" alt="{{ $name }}" class="rounded-circle" 
             onerror="this.src='{{ asset('public/images/default.png') }}'">
    </div>
@else
    <div class="symbol symbol-50px me-5">
        <div class="symbol-label bg-light-primary text-primary fs-6 fw-bolder">
            {{ strtoupper(substr($name, 0, 2)) }}
        </div>
    </div>
@endif 