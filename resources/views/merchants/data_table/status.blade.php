@if($is_active)
    <span class="badge badge-light-success">{{ __('translation.active') }}</span>
@else
    <span class="badge badge-light-danger">{{ __('translation.inactive') }}</span>
@endif 