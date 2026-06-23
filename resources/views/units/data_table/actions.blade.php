<div style="min-width: 100px">
    @php
        $id= $unit->id;
    @endphp
    <button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        Actions
        <i class="ki-duotone ki-down fs-5 ms-1"></i>
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
        <div class="menu-item px-3">
            <a href="{{ route('units.edit', $id) }}" class="menu-link px-3">
                Edit
            </a>
        </div>
        <div class="menu-item px-3">
            <a href="{{ route('units.show', ['status' => true , 'unit' => $id]) }}" class="menu-link px-3">
                Change Status
            </a>
        </div>
        <div class="menu-item px-3">
            <form action="{{ route('units.destroy', $id) }}" method="post" style="display: inline-block; width: 100%;" id="delete-form-{{ $id }}">
                @csrf
                @method('delete')
                <a href="#" onclick="event.preventDefault(); document.getElementById('delete-form-{{ $id }}').submit();" class="menu-link text-danger px-3">
                    Delete
                </a>
            </form>
        </div>
    </div>
</div> 