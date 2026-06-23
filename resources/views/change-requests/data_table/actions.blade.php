<!-- Show Details Button (Always Available) -->
<a href="#" 
   class="btn btn-sm btn-light-info me-2 show-change-request-details" 
   data-id="{{ $changeRequest->id }}"
   data-bs-toggle="modal" 
   data-bs-target="#changeRequestDetailsModal">
    <i class="ki-duotone ki-eye fs-5">
        <span class="path1"></span>
        <span class="path2"></span>
        <span class="path3"></span>
    </i>
    {{ __('translation.show_details') }}
</a>

@if($changeRequest->status === 'pending')
    <a href="{{ route('admin.change-requests.approve', $changeRequest->id) }}" 
       class="btn btn-sm btn-light-success me-2 approve-change-request" 
       data-id="{{ $changeRequest->id }}">
        <i class="ki-duotone ki-check fs-5">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.approve') }}
    </a>
    
    <a href="{{ route('admin.change-requests.reject', $changeRequest->id) }}" 
       class="btn btn-sm btn-light-danger reject-change-request" 
       data-id="{{ $changeRequest->id }}">
        <i class="ki-duotone ki-cross fs-5">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.reject') }}
    </a>
@endif
