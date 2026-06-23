<div class="d-flex justify-content-end flex-shrink-0">
    @if(auth()->user()->can('branches') || auth()->user()->can('view_branches'))
    <a href="{{ route('merchant.branches.show', $branch->id) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
        <i class="ki-duotone ki-eye fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
    </a>
    @endif
    
    @if(auth()->user()->can('branches') || auth()->user()->can('edit_branches'))
    <a href="{{ route('merchant.branches.edit', $branch->id) }}" class="btn btn-icon btn-bg-light btn-active-color-warning btn-sm me-1" {{ $branch->status !== 'pending' ? 'disabled' : '' }}>
        <i class="ki-duotone ki-pencil fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </a>
    @endif
    
    @if(auth()->user()->can('branches') || auth()->user()->can('delete_branches'))
    <button type="button" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm delete-branch" {{ $branch->status !== 'pending' ? 'disabled' : '' }} data-id="{{ $branch->id }}">
        <i class="ki-duotone ki-trash fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </button>
    @endif
</div> 