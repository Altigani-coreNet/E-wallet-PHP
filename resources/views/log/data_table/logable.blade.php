<!--begin::Action=-->
<td class="text-end">
    <div class="d-flex justify-content-end flex-shrink-0">
        @if($item->loggable)
            <a href="{{ route($item->getLoggableRoute(), $item->loggable_id) }}" 
               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
               data-bs-toggle="tooltip"
               title="{{ __('translation.view_details') }}">
                <span class="svg-icon svg-icon-3">
                    <i class="bi bi-eye-fill"></i>
                </span>
            </a>
        @endif
    </div>
</td>
<!--end::Action=-->
