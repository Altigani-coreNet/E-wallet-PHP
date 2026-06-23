@extends('layouts.admin.admin_layout')

@section('main-head', 'User Group Details')

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">User Groups</li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">User Group Details</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <div class="m-0">
        <a href="{{ route('user-groups.edit', $userGroup) }}" class="btn btn-sm btn-primary me-3">
            <i class="ki-duotone ki-pencil fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Edit User Group
        </a>
        <a href="{{ route('user-groups.index') }}" class="btn btn-sm btn-light-danger me-3">
            <i class="ki-duotone ki-arrow-left fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Back to List
        </a>
        <form action="{{ route('user-groups.toggle-status', $userGroup->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-{{ $userGroup->is_active ? 'warning' : 'success' }}">
                <i class="ki-duotone ki-{{ $userGroup->is_active ? 'cross' : 'check' }} fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                {{ $userGroup->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>
</div>
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <div class="row">
                <div class="col-md-12">
                    <!--begin::Card-->
                    <div class="card">
                        <!--begin::Card header-->
                        <div class="card-header border-0 pt-6">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>User Group Information</h2>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Group Name:</label>
                                    <p class="form-control-static">{{ $userGroup->name }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Group ID:</label>
                                    <p class="form-control-static">{{ $userGroup->group_id }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Merchant:</label>
                                    <p class="form-control-static">{{ $userGroup->merchant->name ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Branch:</label>
                                    <p class="form-control-static">{{ $userGroup->branch->name ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Status:</label>
                                    <p class="form-control-static">{!! $userGroup->getStatusWithSpan() !!}</p>
                                </div>
                                {{-- <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Terminal Mode:</label>
                                    <p class="form-control-static">{!! $userGroup->getTerminalModeDisplayAttribute() !!}</p>
                                </div> --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Created:</label>
                                    <p class="form-control-static">{{ $userGroup->created_at->format('M d, Y H:i') }}</p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Description:</label>
                                    <p class="form-control-static">{{ $userGroup->description ?: 'No description provided' }}</p>
                                </div>
                            </div>
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->

                    <!--begin::Card-->
                    <div class="card mt-6">
                        <!--begin::Card header-->
                        <div class="card-header border-0 pt-6">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Users in Group ({{ $userGroup->users->count() }})</h2>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                            @if($userGroup->users->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                        <thead>
                                            <tr class="fw-bold text-muted">
                                                <th class="min-w-150px">Name</th>
                                                <th class="min-w-140px">Email</th>
                                                <th class="min-w-120px">Phone</th>
                                                <th class="min-w-100px">Status</th>
                                                <th class="min-w-100px text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($userGroup->users as $user)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-45px me-5">
                                                                {!! $user->getTableImage() !!}
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <a href="#" class="text-dark fw-bold text-hover-primary fs-6">{{ $user->name }}</a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>{{ $user->phone ?: 'N/A' }}</td>
                                                    <td>{!! $user->getStatusWithSpan() !!}</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-light-primary">
                                                            View
                                                        </a>

                                                        <form action="{{ route('user-groups.remove-user', [$userGroup->id, $user->id]) }}" method="POST" onsubmit="return confirm('Remove this user from the group?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-light-danger">
                                                                <i class="ki-duotone ki-trash fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                                                                Remove
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <i class="ki-duotone ki-user fs-3x text-muted mb-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <p class="text-muted">No users assigned to this group</p>
                                </div>
                            @endif
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->

                    
                </div>

                {{-- <div class="col-md-4">
                    <!--begin::Card-->
                    <div class="card">
                        <!--begin::Card header-->
                        <div class="card-header">
                            <h3 class="card-title">Actions</h3>
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('user-groups.edit', $userGroup) }}" class="btn btn-warning">
                                    <i class="ki-duotone ki-pencil fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Edit User Group
                                </a>
                                
                                <form action="{{ route('user-groups.toggle-status', $userGroup->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-{{ $userGroup->is_active ? 'warning' : 'success' }} w-100">
                                        <i class="ki-duotone ki-{{ $userGroup->is_active ? 'cross' : 'check' }} fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ $userGroup->is_active ? 'Deactivate' : 'Activate' }} User Group
                                    </button>
                                </form>
                                
                                <form action="{{ route('user-groups.destroy', $userGroup->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete this user group?')">
                                        <i class="ki-duotone ki-trash fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Delete User Group
                                    </button>
                                </form>
                            </div>
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div> --}}
            </div>
        </div>
        <!--end::Container-->
    </div>
@endsection 