{{-- @if (auth()->user()->hasPermission('update_branches')) --}}
<div style="min-width: 100px">
    <button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        Actions
        <i class="ki-duotone ki-down fs-5 ms-1"></i>
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
        
        <div class="menu-item px-3">
            <a href="{{ route('branches.show', ['status' => true, 'branch' => $id]) }}" class="menu-link px-3">
                Change Status
            </a>
        </div>

        @if($status === 'pending' || $status === null || $status === 'viewed')
            <div class="menu-item px-3">
                <form action="{{ route('branches.approve', $id) }}" method="post" style="display: inline;">
                    @csrf
                    <button type="submit" class="menu-link px-3 bg-light-success text-success" style="background: none; border: none; width: 100%; text-align: left;">
                        Approve
                    </button>
                </form>
            </div>
            <div class="menu-item px-3">
                <form action="{{ route('branches.reject', $id) }}" method="post" style="display: inline;">
                    @csrf
                    <button type="submit" class="menu-link px-3 bg-light-danger text-danger" style="background: none; border: none; width: 100%; text-align: left;">
                        Reject
                    </button>
                </form>
            </div>
        @elseif($status === 'approved' || $status === 'viewed' || $status === 'rejected')
            <div class="menu-item px-3">
                <form action="{{ route('branches.suspend', $id) }}" method="post" style="display: inline;">
                    @csrf
                    <button type="submit" class="menu-link px-3 text-warning" style="background: none; border: none; width: 100%; text-align: left;">
                        Suspend
                    </button>
                </form>
            </div>
        @elseif($status === 'rejected')
            <div class="menu-item px-3">
                <form action="{{ route('branches.approve', $id) }}" method="post" style="display: inline;">
                    @csrf
                    <button type="submit" class="menu-link px-3 text-success" style="background: none; border: none; width: 100%; text-align: left;">
                        Approve
                    </button>
                </form>
            </div>
        @elseif($status === 'suspended')
            <div class="menu-item px-3">
                <form action="{{ route('branches.unsuspend', $id) }}" method="post" style="display: inline;">
                    @csrf
                    <button type="submit" class="menu-link px-3 text-success" style="background: none; border: none; width: 100%; text-align: left;">
                        Unsuspend
                    </button>
                </form>
            </div>
            <div class="menu-item px-3">
                <form action="{{ route('branches.approve', $id) }}" method="post" style="display: inline;">
                    @csrf
                    <button type="submit" class="menu-link px-3 text-success" style="background: none; border: none; width: 100%; text-align: left;">
                        Approve
                    </button>
                </form>
            </div>
        @endif

        <div class="menu-item px-3">
            <a href="{{  route('branches.edit', $id) }}" class="menu-link px-3">
                Edit
            </a>
        </div>
        <div class="menu-item px-3">
            <form action="{{ route('branches.destroy', $id) }}" method="post">
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