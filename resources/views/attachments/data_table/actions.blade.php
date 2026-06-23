<div style="min-width: 100px">
    <button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        Actions
        <i class="ki-duotone ki-down fs-5 ms-1"></i>
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
        <div class="menu-item px-3">
            <a href="{{ route('attachments.show', ['attachment' => $id]) }}" class="menu-link px-3" target="_blank">
                View
            </a>
        </div>
        <div class="menu-item px-3">
            <a href="{{ route('attachments.download', ['attachment' => $id]) }}" class="menu-link px-3">
                Download
            </a>
        </div>
        <div class="menu-item px-3">
            <form action="{{ route('attachments.delete-file') }}" method="post" style="display: inline-block; width: 100%;">
                @csrf
                <input type="hidden" name="filePath" value="{{ $url }}">
                <button type="submit" class="menu-link px-3 text-danger w-100 text-start">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>
