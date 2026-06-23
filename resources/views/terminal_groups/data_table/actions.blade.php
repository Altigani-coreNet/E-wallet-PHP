{{-- @if (auth()->user()->hasPermission('update_terminal_groups')) --}}
<div style="min-width: 100px">
    <button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        Actions
        <i class="ki-duotone ki-down fs-5 ms-1"></i>
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
        <div class="menu-item px-3">
            <a href="{{ route('terminal-groups.show', ['terminal_group' => $group->id]) }}" class="menu-link px-3">
                View
            </a>
        </div>
        <div class="menu-item px-3">
            <form action="{{ route('terminal-groups.toggle-status', $group->id) }}" method="POST" style="display: inline;">
                @csrf
                @method('POST')
                <a href="#" class="menu-link px-3" onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ $group->is_active ? 'Deactivate' : 'Activate' }}
                </a>
            </form>
        </div>
        <div class="menu-item px-3">
            <a href="{{ route('terminal-groups.edit', $group->id) }}" class="menu-link px-3">
                Edit
            </a>
        </div>
        <div class="menu-item px-3">
            <form action="{{ route('terminal-groups.destroy', $group->id) }}" method="post">
                @csrf
                @method('delete')
                <a href="#" class="menu-link px-3 text-danger" onclick="event.preventDefault(); this.closest('form').submit();">
                    Delete
                </a>
            </form>
        </div>
    </div>
</div>
{{-- @endif --}} 