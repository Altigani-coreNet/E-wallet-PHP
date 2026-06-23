@if($status == 'APPROVED')
    <span class="badge badge-light-success">Approved</span>
@elseif($status == 'DECLINED')
    <span class="badge badge-light-danger">Declined</span>
@elseif($status == 'PENDING')
    <span class="badge badge-light-warning">Pending</span>
@else
    <span class="badge badge-light-secondary">{{ $status ?? 'Unknown' }}</span>
@endif 