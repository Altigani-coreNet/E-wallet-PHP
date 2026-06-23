<div class="d-flex justify-content-end flex-shrink-0">
   
    <a href="{{ route('merchant.branches.edit', $id) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="Edit">
        <i class="ki-duotone ki-pencil fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </a>
    <button type="button" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm delete-branch" data-id="{{ $id }}" title="Delete">
        <i class="ki-duotone ki-trash fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </button>
</div> 