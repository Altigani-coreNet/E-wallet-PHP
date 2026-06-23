@foreach($user->roles as $role)
    <span class="badge badge-light-success">{{ $role->name }}</span>
@endforeach 