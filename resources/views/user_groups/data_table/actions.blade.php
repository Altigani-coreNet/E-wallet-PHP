<div class="d-flex justify-content-end flex-shrink-0">
    <a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        <i class="ki-duotone ki-gear fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </a>
    <!--begin::Menu-->
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
        <!--begin::Menu item-->
        <div class="menu-item px-3">
            <a href="{{ route('user-groups.show', ['user_group' => $group->id]) }}" class="menu-link px-3">
                View
            </a>
        </div>
        <!--end::Menu item-->
        <!--begin::Menu item-->
        <div class="menu-item px-3">
            <form action="{{ route('user-groups.toggle-status', $group->id) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="menu-link px-3" style="background: none; border: none; width: 100%; text-align: left;">
                    {{ $group->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
        <!--end::Menu item-->
        <!--begin::Menu item-->
        <div class="menu-item px-3">
            <a href="{{ route('user-groups.edit', $group->id) }}" class="menu-link px-3">
                Edit
            </a>
        </div>
        <!--end::Menu item-->
        <!--begin::Menu item-->
        <div class="menu-item px-3">
            <form action="{{ route('user-groups.destroy', $group->id) }}" method="post">
                @csrf
                @method('DELETE')
                <button type="submit" class="menu-link px-3 text-danger" style="background: none; border: none; width: 100%; text-align: left;" onclick="return confirm('Are you sure you want to delete this user group?')">
                    Delete
                </button>
            </form>
        </div>
        <!--end::Menu item-->
    </div>
    <!--end::Menu-->
</div> 