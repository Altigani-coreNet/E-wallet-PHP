{{-- @if (auth()->user()->hasPermission('update_terminals')) --}}
<div style="min-width: 100px">
    <button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        {{ __('translation.actions') }}
        <i class="ki-duotone ki-down fs-5 ms-1"></i>
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
        <div class="menu-item px-3">
            <a href="{{ route('terminals.show', ['terminal' => $id]) }}" class="menu-link px-3">
                {{ __('translation.view') }}
            </a>
        </div>
        <div class="menu-item px-3">
            <a href="{{ route('terminals.show', ['status' => true, 'terminal' => $id]) }}" class="menu-link px-3">
                {{ __('translation.change_status') }}
            </a>
        </div>
        <div class="menu-item px-3">
            <a href="{{ route('terminals.edit', $id) }}" class="menu-link px-3">
                {{ __('translation.edit') }}
            </a>
        </div>
        <div class="menu-item px-3">
            <form action="{{ route('terminals.destroy', $id) }}" method="post">
                @csrf
                @method('delete')
                <a href="#" class="menu-link px-3 text-danger" onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('translation.delete') }}
                </a>
            </form>
        </div>
    </div>
</div>
{{-- @endif --}} 