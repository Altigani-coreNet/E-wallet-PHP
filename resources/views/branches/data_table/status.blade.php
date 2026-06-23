@if($branch->status === 'pending')
    <span class="badge badge-warning">Pending</span>
@elseif($branch->status === 'approved')
    <span class="badge badge-success">Approved</span>
@elseif($branch->status === 'rejected')
    <span class="badge badge-danger">Rejected</span>
@elseif($branch->status === 'suspended')
    <span class="badge badge-warning">Suspended</span>
@elseif($branch->status === 'viewed')
    <span class="badge badge-info">Viewed</span>
@elseif($branch->status === 'deleted')
    <span class="badge badge-secondary">Deleted</span>
@else
    <span class="badge badge-secondary">Unknown</span>
@endif 