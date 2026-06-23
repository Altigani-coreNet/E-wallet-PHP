@if(auth()->user()->can('customers') || auth()->user()->can('edit_customers') || auth()->user()->can('delete_customers'))
<div style="min-width: 120px">
    <button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        {{ __('translation.actions') }}
        <i class="ki-duotone ki-down fs-5 ms-1"></i>
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-4" data-kt-menu="true">
        @if(auth()->user()->can('customers'))
        <div class="menu-item px-3">
            <a href="{{ route('merchant.customers.show', $customer->id) }}" class="menu-link px-3">{{ __('translation.view') }}</a>
        </div>
        @endif
        @if(auth()->user()->can('customers') || auth()->user()->can('edit_customers'))
        <div class="menu-item px-3">
            <a href="{{ route('merchant.customers.edit', $customer) }}" class="menu-link px-3">{{ __('translation.edit') }}</a>
        </div>
        @endif
        @if(auth()->user()->can('customers') || auth()->user()->can('delete_customers'))
        <div class="menu-item px-3">
            <button type="button" 
                    class="menu-link px-3 text-danger w-100 text-start delete-customer" 
                    data-id="{{ $customer->id }}" 
                    data-name="{{ $customer->name }}">
                {{ __('translation.delete') }}
            </button>
        </div>
        @endif
    </div>
</div>
@endif
