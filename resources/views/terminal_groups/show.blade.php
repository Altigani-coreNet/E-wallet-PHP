@extends('layouts.admin.admin_layout')

@section('main-head', 'Terminal Group Details')

@section('breadcrumbs')
<!--begin::Breadcrumb-->
<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
    <!--begin::Item-->
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">
            <i class="ki-duotone ki-home fs-6 text-muted me-1">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.dashboard') }}
        </a>
    </li>
    <!--end::Item-->
    <!--begin::Item-->
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-400 w-5px h-2px"></span>
    </li>
    <!--end::Item-->
    <!--begin::Item-->
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('terminal-groups.index') }}" class="text-muted text-hover-primary">
            {{ __('translation.terminal_groups') }}
        </a>
    </li>
    <!--end::Item-->
    <!--begin::Item-->
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-400 w-5px h-2px"></span>
    </li>
    <!--end::Item-->
    <!--begin::Item-->
    <li class="breadcrumb-item text-muted">{{ __('translation.terminal_group_details') }}</li>
    <!--end::Item-->
</ul>
<!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="card-toolbar">
    <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
        <a href="{{ route('terminal-groups.edit', $terminalGroup) }}" class="btn btn-primary btn-sm me-3">
            <i class="ki-duotone ki-pencil fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.edit') }}
        </a>
        
        <form action="{{ route('terminal-groups.toggle-status', $terminalGroup->id) }}" method="POST" class="d-inline me-3">
            @csrf
            @method('POST')
            <button type="submit" class="btn btn-{{ $terminalGroup->is_active ? 'success' : 'secondary' }} btn-sm">
                <i class="ki-duotone {{ $terminalGroup->is_active ? 'ki-check' : 'ki-cross' }} fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                {{ $terminalGroup->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
        
        <form action="{{ route('terminal-groups.destroy', $terminalGroup->id) }}" method="POST" 
              onsubmit="return confirm('Are you sure you want to delete this terminal group?')" class="d-inline me-3">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="ki-duotone ki-trash fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                Delete Group
            </button>
        </form>
        
        <a href="{{ route('terminal-groups.index') }}" class="btn btn-sm btn-light-danger">
            <i class="ki-duotone ki-arrow-left fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.back') }}
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <!-- Terminal Group Information Card -->
        <div class="card mb-5 mb-xl-8">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h2 class="fw-bold">Terminal Group Details</h2>
                </div>
            </div>
            
            <div class="card-body pt-0">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold text-muted">Group Name</label>
                        <p class="form-control-plaintext">{{ $terminalGroup->name }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold text-muted">Group ID</label>
                        <p class="form-control-plaintext">
                            <span class="badge badge-primary fs-6">{{ $terminalGroup->group_id }}</span>
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold text-muted">Merchant</label>
                        <p class="form-control-plaintext">{{ $terminalGroup->merchant ? $terminalGroup->merchant->name : 'N/A' }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold text-muted">Status</label>
                        <p class="form-control-plaintext">
                            <span class="badge badge-{{ $terminalGroup->is_active ? 'success' : 'danger' }} fs-6">
                                {{ $terminalGroup->status_display_name }}
                            </span>
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold text-muted">Created Date</label>
                        <p class="form-control-plaintext">{{ $terminalGroup->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold text-muted">Updated Date</label>
                        <p class="form-control-plaintext">{{ $terminalGroup->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                    
                    @if($terminalGroup->description)
                    <div class="col-12 mb-4">
                        <label class="form-label fw-bold text-muted">Description</label>
                        <p class="form-control-plaintext">{{ $terminalGroup->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Terminals Table Card -->
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h3 class="fw-bold">Terminals in this Group</h3>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex justify-content-end">
                        <span class="badge badge-light-primary fs-7">{{ $terminalGroup->terminals->count() }} Total</span>
                    </div>
                </div>
            </div>
            
            <div class="card-body pt-0">
                @if($terminalGroup->terminals->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="terminals-table">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                    <th class="text-dark">{{ __('translation.id') }}</th>
                                    <th class="min-w-125px text-dark">{{ __('translation.name') }}</th>
                                    <th class="min-w-125px text-dark">{{ __('translation.terminal_id') }}</th>
                                    <th class="min-w-125px text-dark">{{ __('translation.model') }}</th>
                                    <th class="min-w-125px text-dark">{{ __('translation.manufacturer') }}</th>
                                    <th class="text-dark">{{ __('translation.status') }}</th>
                                    <th class="text-end min-w-125px  text-dark">{{ __('translation.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                @foreach($terminalGroup->terminals as $terminal)
                                    <tr>
                                        <td>{{ $terminal->id }}</td>
                                        <td>{{ $terminal->name }}</td>
                                        <td>
                                            <span class="badge badge-light-info fs-7">{{ $terminal->terminal_id }}</span>
                                        </td>
                                        <td>{{ $terminal->model ?? 'N/A' }}</td>
                                        <td>{{ $terminal->manufacturer ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $terminal->is_active ? 'success' : 'danger' }} fs-7">
                                                {{ $terminal->status_display_name }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('terminals.show', $terminal->id) }}" class="btn btn-sm btn-light-primary">
                                               
                                                View
                                            </a>
                                            <form action="{{ route('terminal-groups.remove-terminal', $terminalGroup->id) }}" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to remove this terminal from the group?')" 
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="terminal_id" value="{{ $terminal->id }}">
                                                <button type="submit" class="btn btn-sm btn-light-danger" title="Remove from Group">
                                                    <i class="ki-duotone ki-cross fs-2">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                    Unsign The Device
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
                        <div class="symbol symbol-100px symbol-circle mb-5">
                            <div class="symbol-label bg-light-warning">
                                <i class="ki-duotone ki-warning fs-2x text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <h3 class="text-gray-600 mb-2">No Terminals Assigned</h3>
                        <p class="text-muted fs-6">This terminal group doesn't have any terminals assigned to it yet.</p>
                        <a href="{{ route('terminal-groups.edit', $terminalGroup) }}" class="btn btn-primary">
                            <i class="ki-duotone ki-plus fs-2 me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Assign Terminals
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 