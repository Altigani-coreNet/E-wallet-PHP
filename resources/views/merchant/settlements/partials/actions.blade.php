@if(auth()->user()->can('settlements') || auth()->user()->can('view_settlements'))
<!--begin::Action buttons-->
<div class="d-flex justify-content-end flex-shrink-0">
    <!--begin::View button-->
    <a href="{{ route('merchant.settlements.show', $settlement->id) }}" 
       class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
       data-bs-toggle="tooltip" 
       data-bs-placement="top" 
       title="{{ __('translation.view_settlement') }}">
        <i class="ki-duotone ki-eye fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
    </a>
    <!--end::View button-->
</div>
<!--end::Action buttons-->
@endif
