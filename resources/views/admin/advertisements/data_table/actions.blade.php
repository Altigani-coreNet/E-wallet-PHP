<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.advertisements.edit', $advertisement->id) }}" 
       class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
        <i class="ki-duotone ki-pencil fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </a>
    
    <button type="button" 
            class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm delete-advertisement" 
            data-url="{{ route('admin.advertisements.destroy', $advertisement->id) }}">
        <i class="ki-duotone ki-trash fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
            <span class="path4"></span>
            <span class="path5"></span>
        </i>
    </button>
</div>

