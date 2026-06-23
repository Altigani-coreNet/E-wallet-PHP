<div class="d-flex">
    <a href="{{ route('admin.transactions.show', $transaction->id) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
        <i class="ki-duotone ki-eye fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </a>
    <a href="{{ route('admin.transactions.edit', $transaction->id) }}" class="btn btn-icon btn-bg-light btn-active-color-warning btn-sm me-1">
        <i class="ki-duotone ki-pencil fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </a>
    <form action="{{ route('admin.transactions.destroy', $transaction->id) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm" onclick="return confirm('Are you sure you want to delete this transaction?')">
            <i class="ki-duotone ki-trash fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </button>
    </form>
</div> 