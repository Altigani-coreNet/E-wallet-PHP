<!--begin::Action buttons-->
<div class="d-flex justify-content-end flex-shrink-0">
    <!--begin::View button-->
    <a href="{{ route('merchant.transactions.show', $transaction->id) }}" 
       class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
       data-bs-toggle="tooltip" 
       data-bs-placement="top" 
       title="{{ __('translation.view_transaction') }}">
        <i class="ki-duotone ki-eye fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
    </a>
    <!--end::View button-->
</div>
<!--end::Action buttons-->
