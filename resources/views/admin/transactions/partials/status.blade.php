@if($status == 'APPROVED')
    <span class="badge badge-light-success">{{ __('translation.APPROVED') }}</span>
@elseif($status == 'DECLINED')
    <span class="badge badge-light-danger">{{ __('translation.DECLINED') }}</span>
@elseif($status == 'PENDING')
    <span class="badge badge-light-warning">{{ __('translation.PENDING') }}</span>
@elseif($status == 'CAPTURED')
    <span class="badge badge-light-info">{{ __('translation.CAPTURED') }}</span>
@elseif($status == 'VOIDED')
    <span class="badge badge-light-dark">{{ __('translation.VOIDED') }}</span>
@elseif($status == 'REFUNDED')
    <span class="badge badge-light-primary">{{ __('translation.REFUNDED') }}</span>
@elseif($status == 'FAILED')
    <span class="badge badge-light-danger">{{ __('translation.FAILED') }}</span>
@elseif($status == 'PROCESSED')
    <span class="badge badge-light-success">{{ __('translation.PROCESSED') }}</span>
@elseif($status == 'CANCELLED')
    <span class="badge badge-light-secondary">{{ __('translation.CANCELLED') }}</span>
@elseif($status == 'EXPIRED')
    <span class="badge badge-light-warning">{{ __('translation.EXPIRED') }}</span>
@elseif($status == 'REVERSED')
    <span class="badge badge-light-dark">{{ __('translation.REVERSED') }}</span>
@else
    <span class="badge badge-light-secondary">{{ $status ?? __('translation.UNKNOWN') }}</span>
@endif
