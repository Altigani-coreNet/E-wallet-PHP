@extends('layouts.merchant.merchant_layout')

@section('main-head', 'Terminal Group Details')

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <div class="card">
            <div class="card-header border-0">
                <div class="card-title">
                    <h2>Terminal Group Details</h2>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
                        <a href="{{ route('merchant.terminal-groups.edit', $terminalGroup) }}" class="btn btn-primary me-3">
                            <i class="ki-duotone ki-pencil fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('translation.edit') }}
                        </a>
                        <a href="{{ route('merchant.terminal-groups.index') }}" class="btn btn-light-danger">
                            <i class="ki-duotone ki-arrow-left fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('translation.back') }}
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-6">
                <div class="row">
                    <!-- Terminal Group Information -->
                    <div class="col-md-9">
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
                                <label class="form-label fw-bold text-muted">Branch</label>
                                <p class="form-control-plaintext">{{ $terminalGroup->branch ? $terminalGroup->branch->name : 'N/A' }}</p>
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
                        
                        <!-- Terminals Information -->
                        <div class="separator my-8"></div>
                        <h4 class="mb-4">Terminals in this Group ({{ $terminalGroup->terminals->count() }})</h4>
                        
                        @if($terminalGroup->terminals->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Terminal ID</th>
                                            <th>Model</th>
                                            <th>Manufacturer</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($terminalGroup->terminals as $terminal)
                                            <tr>
                                                <td>{{ $terminal->id }}</td>
                                                <td>{{ $terminal->name }}</td>
                                                <td><span class="badge badge-info">{{ $terminal->terminal_id }}</span></td>
                                                <td>{{ $terminal->model ?? 'N/A' }}</td>
                                                <td>{{ $terminal->manufacturer ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $terminal->is_active ? 'success' : 'danger' }}">
                                                        {{ $terminal->status_display_name }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                No terminals assigned to this group.
                            </div>
                        @endif
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="{{ route('merchant.terminal-groups.edit', $terminalGroup) }}" class="btn btn-warning">
                                        <i class="ki-duotone ki-pencil fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Edit Group
                                    </a>
                                    <form action="{{ route('merchant.terminal-groups.toggle-status', $terminalGroup->id) }}" method="POST">
                                        @csrf
                                        @method('POST')
                                        <button type="submit" class="btn btn-{{ $terminalGroup->is_active ? 'success' : 'secondary' }} w-100">
                                            <i class="ki-duotone {{ $terminalGroup->is_active ? 'ki-check' : 'ki-cross' }} fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ $terminalGroup->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('merchant.terminal-groups.destroy', $terminalGroup->id) }}" method="POST" 
                                          onsubmit="return confirm('Are you sure you want to delete this terminal group?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="ki-duotone ki-trash fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            Delete Group
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 