@if(auth()->user()->can('users') || auth()->user()->can('view_users') || auth()->user()->can('edit_users') || auth()->user()->can('delete_users'))
<div style="min-width: 100px">
    <button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        Actions
        <i class="ki-duotone ki-down fs-5 ms-1"></i>
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
        @if(auth()->user()->can('users') || auth()->user()->can('view_users'))
        <div class="menu-item px-3">
            <a href="{{ route('merchant.users.show', [ 'user' => $id]) }}" class="menu-link px-3">
                View
            </a>
        </div>
        @endif
        @if(auth()->user()->can('users') || auth()->user()->can('edit_users'))
        <div class="menu-item px-3">
            <a href="{{ route('merchant.users.show', ['status' => true , 'user' => $id]) }}" class="menu-link px-3">
                Change Status
            </a>
        </div>
        <div class="menu-item px-3">
            <a href="{{ route('merchant.users.edit', $id) }}" class="menu-link px-3">
                Edit
            </a>
        </div>
        @endif
        @if(auth()->user()->can('users') || auth()->user()->can('delete_users'))
        <div class="menu-item px-3">
            <form action="{{ route('merchant.users.destroy', $id) }}" method="post" style="display: inline-block; width: 100%;">
                @csrf
                @method('delete')
                <button type="submit" class="menu-link px-3 text-danger w-100 text-start">
                    Delete
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endif 