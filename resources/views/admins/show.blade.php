@extends('layouts.admin.admin_layout')

@section('title', 'Admin Details')

@section('content')
<div class="d-flex flex-column flex-xl-row">
    <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
        <div class="card mb-5 mb-xl-8">
            <div class="card-body pt-15">
                <div class="d-flex flex-center flex-column mb-5">
                    <div class="symbol symbol-150px symbol-circle mb-7">
                        <img src="{{ $admin->profile_image ? asset($admin->profile_image) : asset('assets/media/avatars/300-1.jpg') }}" alt="image">
                    </div>
                    <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold mb-1">{{ $admin->name }}</a>
                    <a href="mailto:{{ $admin->email }}" class="fs-5 fw-semibold text-muted text-hover-primary mb-6">{{ $admin->email }}</a>
                </div>
                <div class="d-flex flex-stack fs-4 py-3">
                    <div class="fw-bold">Details</div>
                    <div class="badge {{ $admin->status === 'active' ? 'badge-light-success' : 'badge-light-danger' }} d-inline">{{ ucfirst($admin->status) }}</div>
                </div>
                <div class="separator separator-dashed my-3"></div>
                <div class="pb-5 fs-6">
                    <div class="fw-bold mt-5">Admin ID</div>
                    <div class="text-gray-600">#{{ $admin->id }}</div>
                    <div class="fw-bold mt-5">Phone</div>
                    <div class="text-gray-600">{{ $admin->phone ?: 'No phone provided' }}</div>
                    <div class="fw-bold mt-5">Created</div>
                    <div class="text-gray-600">{{ $admin->created_at?->format('M d, Y H:i A') }}</div>
                    <div class="fw-bold mt-5">Updated</div>
                    <div class="text-gray-600">{{ $admin->updated_at?->format('M d, Y H:i A') }}</div>
                    <div class="fw-bold mt-5">Custom Regions</div>
                    <div class="text-gray-600">
                        <span class="badge {{ $admin->custom_region ? 'badge-success' : 'badge-light-secondary' }} d-inline">
                            {{ $admin->custom_region ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    @if($admin->custom_region && $admin->countries->count() > 0)
                    <div class="fw-bold mt-5">Regions</div>
                    <div class="text-gray-600">
                        @foreach($admin->countries as $country)
                            <div class="d-flex align-items-center mb-1">
                                @if($country->getFlagUrl())
                                    <img src="{{ $country->getFlagUrl() }}" alt="{{ $country->name }}" class="symbol symbol-15px me-2">
                                @endif
                                <small>{{ $country->name }}</small>
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="d-flex gap-3 mt-5">
                    <a href="{{ route('admins.index') }}" class="btn btn-light-secondary w-100"><i class="ki-duotone ki-arrow-left fs-3"></i>Back</a>
                    <a href="{{ route('admins.edit', $admin->id) }}" class="btn btn-light-primary w-100"><i class="ki-duotone ki-pencil fs-3"></i>Edit</a>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-lg-row-fluid ms-lg-15">
        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#admin_overview" aria-selected="true" role="tab">Overview</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#admin_events" aria-selected="false" role="tab">Events</a>
            </li>
        </ul>

        <div class="tab-content" id="adminTabContent">
            <div class="tab-pane fade show active" id="admin_overview" role="tabpanel">
                <div class="card pt-4 mb-6">
                    <div class="card-header border-0">
                        <div class="card-title"><h2>Admin Information</h2></div>
                        <div class="card-toolbar">
                            <a href="{{ route('admins.edit', $admin->id) }}" class="btn btn-sm btn-light-primary"><i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>Edit Admin</a>
                        </div>
                    </div>
                    <div class="card-body pt-0 pb-5">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <tbody class="fs-6 fw-semibold text-gray-600">
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Admin ID</td>
                                        <td class="text-gray-800">#{{ $admin->id }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Name</td>
                                        <td class="text-gray-800">{{ $admin->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Email</td>
                                        <td class="text-gray-800"><a href="mailto:{{ $admin->email }}" class="text-gray-900 text-hover-primary">{{ $admin->email }}</a></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Phone</td>
                                        <td class="text-gray-800">{{ $admin->phone ?: 'No phone provided' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Status</td>
                                        <td class="text-gray-800">
                                            <span class="badge {{ $admin->status === 'active' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($admin->status) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Created</td>
                                        <td class="text-gray-800">{{ $admin->created_at?->format('M d, Y H:i:s') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Last Updated</td>
                                        <td class="text-gray-800">{{ $admin->updated_at?->format('M d, Y H:i:s') }}</td>
                                    </tr>
                                    @if($admin->roles->count() > 0)
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Roles</td>
                                        <td class="text-gray-800">
                                            @foreach($admin->roles as $role)
                                                <span class="badge badge-light-primary me-2">{{ $role->name }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                    @endif
                                    @if(method_exists($admin, 'permissions') && $admin->permissions->count() > 0)
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Permissions</td>
                                        <td class="text-gray-800">
                                            @foreach($admin->permissions as $permission)
                                                <span class="badge badge-light-info me-2 mb-1">{{ $permission->name }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Custom Regions</td>
                                        <td class="text-gray-800">
                                            <span class="badge {{ $admin->custom_region ? 'badge-success' : 'badge-light-secondary' }}">
                                                {{ $admin->custom_region ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($admin->custom_region && $admin->countries->count() > 0)
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Assigned Regions</td>
                                        <td class="text-gray-800">
                                            @foreach($admin->countries as $country)
                                                <span class="d-flex align-items-center mb-2">
                                                    @if($country->getFlagUrl())
                                                        <img src="{{ $country->getFlagUrl() }}" alt="{{ $country->name }}" class="symbol symbol-20px me-2">
                                                    @endif
                                                    <span class="badge badge-light-primary me-2">{{ $country->name }}</span>
                                                    @if($country->short_name)
                                                        <small class="text-muted">({{ $country->short_name }})</small>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="admin_events" role="tabpanel">
                <div class="card pt-4 mb-6">
                    <div class="card-header border-0">
                        <div class="card-title"><h2>Events</h2></div>
                    </div>
                    <div class="card-body pt-0 pb-5">
@php
    $logs = \App\Models\Log::where('loggable_type', \App\Models\Admin::class)
        ->where('loggable_id', $admin->id)
        ->latest()
        ->limit(50)
        ->get();
@endphp
                        @if($logs->isEmpty())
                            <div class="text-center text-muted py-10">No events found for this admin.</div>
                        @else
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th>When</th>
                                        <th>Action</th>
                                        <th>By</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-6 text-gray-700">
                                    @foreach($logs as $log)
                                        <tr>
                                            <td>{{ $log->created_at?->format('M d, Y H:i:s') }} <span class="text-muted ms-2">({{ $log->time }})</span></td>
                                            <td>{!! $log->getLabelWithSpan() !!}</td>
                                            <td>{{ optional($log->user)->name ?? 'System' }}</td>
                                            <td>{{ $log->text }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end py-6 px-0">
            <form method="POST" action="{{ route('admins.destroy', $admin->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this admin?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Admin
                </button>
            </form>
        </div>
    </div>
</div>
@endsection