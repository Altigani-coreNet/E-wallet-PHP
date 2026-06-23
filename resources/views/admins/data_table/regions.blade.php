@php($countries = $admin->countries)
@if($countries && $countries->count())
    @foreach($countries as $country)
        <span class="badge badge-light-primary me-1">{{ $country->name }}</span>
    @endforeach
@else
    <span class="text-muted">All Countries</span>
@endif

