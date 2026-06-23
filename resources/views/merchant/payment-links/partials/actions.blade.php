@if(auth()->user()->can('payment_links') || auth()->user()->can('view_payment_links') || auth()->user()->can('edit_payment_links') || auth()->user()->can('delete_payment_links'))
<div style="min-width: 120px">
    <button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        {{ __('translation.actions') }}
        <i class="ki-duotone ki-down fs-5 ms-1"></i>
    </button>
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-4" data-kt-menu="true">
        {{-- <div class="menu-item px-3">
            <a href="{{ $showUrl }}" class="menu-link px-3">{{ __('translation.view') }}</a>
        </div> --}}
        @if(auth()->user()->can('payment_links') || auth()->user()->can('view_payment_links'))
        <div class="menu-item px-3">
            <a href="{{ $row->link }}" class="menu-link px-3" target="_blank">{{ __('translation.pay') }}</a>
        </div>
        @endif
        @if(auth()->user()->can('payment_links') || auth()->user()->can('edit_payment_links'))
        <div class="menu-item px-3">
            <a href="{{ $editUrl }}" class="menu-link px-3">{{ __('translation.edit') }}</a>
        </div>
        <div class="menu-item px-3">
            <a href="#" class="menu-link px-3 reschedule-action" data-id="{{ $row->id }}">{{ __('translation.reschedule') }}</a>
        </div>
        <div class="menu-item px-3">
            <a href="#" class="menu-link px-3 send-action" data-id="{{ $row->id }}" data-link="{{ $row->link }}">{{ __('translation.send') }}</a>
        </div>
        @endif
        @if(auth()->user()->can('payment_links') || auth()->user()->can('delete_payment_links'))
        <div class="menu-item px-3">
            <form action="{{ $deleteUrl }}" method="POST" style="display: inline-block; width: 100%;">
                @csrf
                @method('DELETE')
                <button type="submit" class="menu-link px-3 text-danger w-100 text-start" onclick="return confirm('{{ __("translation.are_you_sure") }}')">
                    {{ __('translation.delete') }}
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endif
