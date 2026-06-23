@if($card_number)
    <div class="d-flex align-items-center">
        <i class="ki-duotone ki-credit-cart fs-2 me-2 text-gray-600"></i>
        <span class="text-gray-800 fw-bold">{{ substr($card_number, 0, 4) }} **** **** {{ substr($card_number, -4) }}</span>
    </div>
@else
    <span class="text-muted">N/A</span>
@endif
