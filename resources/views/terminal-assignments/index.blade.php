@extends('layouts.admin.admin_layout')

@section('main-head', 'Terminal Assignments')

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
        <li class="breadcrumb-item text-muted">Terminal Assignments</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <div class="m-0">
        <a href="{{ route('users.index') }}" class="btn btn-light-danger btn-sm me-3">
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
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <form action="{{ route('terminal-assignments.store') }}" method="POST">
                @csrf
                
                <!--begin::Assignment Type Selection-->
                <div class="row mb-6">
                 
                </div>
                <!--end::Assignment Type Selection-->

                <div class="row">
                    <div class="row col-md-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>Assign Terminals to Users</h2>
                                </div>
                                <!--begin::Card toolbar-->
                                <div class="card-toolbar">

                                  
                                    <!--begin::Toolbar-->
                                    <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
                                        <a href="{{ route('users.index') }}" class="btn btn-light-danger me-3">
                                            <i class="ki-duotone ki-arrow-left fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ __('translation.back') }}
                                        </a>
                                    </div>
                                    <!--end::Toolbar-->
                                </div>
                                <!--end::Card toolbar-->
                            </div>
                            
                            <div class="card-body p-3">

                                
                                <div class="col-md-12">
                                    <div class="">
                                        <!-- General Validation Errors -->
                                        @if ($errors->any())
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                <div class="d-flex">
                                                    <i class="ki-duotone ki-cross-circle fs-2hx text-danger me-4">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                    <div class="d-flex flex-column">
                                                        <h4 class="mb-1">{{ __('translation.validation_errors') }}</h4>
                                                        <ul class="mb-0">
                                                            @foreach ($errors->all() as $error)
                                                                <li>{{ $error }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        @endif
                                        
                                        <div class="row">
                                            <!-- User Information -->
                                            <div class="col-12 mb-4">
                                                <div class="alert alert-info">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-duotone ki-user fs-2x text-info me-4"></i>
                                                        <div>
                                                            <strong>User:</strong> {{ $user->name }} ({{ $user->email }})
                                                            <br>
                                                            <strong>Merchant:</strong> {{ $merchant->name ?? 'N/A' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Hidden User ID -->
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">

                                                                                         <div class="col-12">
                                                 <div class="p-4">
                                                     <label class="form-label fw-bold fs-6 mb-3">Select Assignment Type</label>
                                                     <div data-kt-buttons="true" class="row justify-content-around">
                                                         <!--begin::Radio button - User Groups-->
                                                         <div class="col-6 mb-3">
                                                             <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6 w-100">
                                                                 <div class="d-flex align-items-center me-2">
                                                                     <!--begin::Radio-->
                                                                     <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                                         <input class="form-check-input" type="radio" name="assignment_type" value="user_groups" checked="checked"/>
                                                                     </div>
                                                                     <!--end::Radio-->
                         
                                                                     <!--begin::Info-->
                                                                     <div class="flex-grow-1">
                                                                         <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                                             Assign with User Groups
                                                                             <span class="badge badge-light-success ms-2 fs-7">Recommended</span>
                                                                         </h2>
                                                                         <div class="fw-semibold opacity-50">
                                                                             Assign terminals based on user group membership
                                                                         </div>
                                                                     </div>
                                                                     <!--end::Info-->
                                                                 </div>
                                                             </label>
                                                         </div>
                                                         <!--end::Radio button-->
                         
                                                         <!--begin::Radio button - Terminal Groups-->
                                                         <div class="col-6 mb-3">
                                                             <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6 w-100">
                                                                 <div class="d-flex align-items-center me-2">
                                                                     <!--begin::Radio-->
                                                                     <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                                         <input class="form-check-input" type="radio" name="assignment_type" value="terminal_groups"/>
                                                                     </div>
                                                                     <!--end::Radio-->
                         
                                                                     <!--begin::Info-->
                                                                     <div class="flex-grow-1">
                                                                         <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                                             Assign to Terminal Groups
                                                                         </h2>
                                                                         <div class="fw-semibold opacity-50">
                                                                             Assign terminals by selecting terminal groups
                                                                         </div>
                                                                     </div>
                                                                     <!--end::Info-->
                                                                 </div>
                                                             </label>
                                                         </div>
                                                         <!--end::Radio button-->
                         
                                                         <!--begin::Radio button - Individual Terminals-->
                                                         <div class="col-6 mb-3">
                                                             <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6 w-100">
                                                                 <div class="d-flex align-items-center me-2">
                                                                     <!--begin::Radio-->
                                                                     <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                                         <input class="form-check-input" type="radio" name="assignment_type" value="individual_terminals"/>
                                                                     </div>
                                                                     <!--end::Radio-->
                         
                                                                     <!--begin::Info-->
                                                                     <div class="flex-grow-1">
                                                                         <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                                             Assign Individual Terminals
                                                                         </h2>
                                                                         <div class="fw-semibold opacity-50">
                                                                             Select specific terminals for assignment
                                                                         </div>
                                                                     </div>
                                                                     <!--end::Info-->
                                                                 </div>
                                                             </label>
                                                         </div>
                                                         <!--end::Radio button-->
                         
                                                         <!--begin::Radio button - All Terminals-->
                                                         <div class="col-6 mb-3">
                                                             <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6 w-100">
                                                                 <div class="d-flex align-items-center me-2">
                                                                     <!--begin::Radio-->
                                                                     <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                                         <input class="form-check-input" type="radio" name="assignment_type" value="all_terminals"/>
                                                                     </div>
                                                                     <!--end::Radio-->
                         
                                                                     <div class="flex-grow-1">
                                                                         <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                                             Assign All Terminals
                                                                         </h2>
                                                                         <div class="fw-semibold opacity-50">
                                                                             Assign all available terminals to the user
                                                                         </div>
                                                                     </div>
                                                                     <!--end::Info-->
                                                                 </div>
                                                             </label>
                                                         </div>
                                                         <!--end::Radio button-->
                                                     </div>
                                                 </div>
                                             </div>
                                            
                                            <!-- Branch Selection -->
                                            <div class="col-md-6 mb-3">
                                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                                <select class="form-control @error('branch_id') is-invalid @enderror" 
                                                        id="branch_id" name="branch_id" required>
                                                    <option value="">{{ __('translation.select_branch') }}</option>
                                                    @foreach($branches as $branch)
                                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                            {{ $branch->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('branch_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- User Groups Selection -->
                                            <div class="col-md-6 mb-3" id="user_groups_section">
                                                <label for="user_group_ids" class="form-label">User Groups <span class="text-danger">*</span></label>
                                                <select name="user_group_ids[]" id="user_group_ids" class="form-select has_select_2 @error('user_group_ids') is-invalid @enderror" 
                                                        multiple required
                                                        data-url="{{ route('user-groups.select') }}"
                                                        data-placeholder="Select user groups">
                                                </select>
                                                @error('user_group_ids')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted">Select one or more user groups for terminal assignment</small>
                                            </div>

                                            <!-- Terminal Groups Selection -->
                                            <div class="col-md-6 mb-3" id="terminal_groups_section" style="display: none;">
                                                <label for="terminal_group_ids" class="form-label">Terminal Groups <span class="text-danger">*</span></label>
                                                <select name="terminal_group_ids[]" id="terminal_group_ids" class="form-select has_select_2 @error('terminal_group_ids') is-invalid @enderror" 
                                                        multiple
                                                        data-url="{{ route('terminal-groups.select') }}"
                                                        data-placeholder="Select terminal groups">
                                                </select>
                                                @error('terminal_group_ids')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted">Select one or more terminal groups for assignment</small>
                                            </div>

                                            <!-- Individual Terminals Selection -->
                                            <div class="col-md-6 mb-3" id="individual_terminals_section" style="display: none;">
                                                <label for="terminal_ids" class="form-label">Individual Terminals <span class="text-danger">*</span></label>
                                                <select name="terminal_ids[]" id="terminal_ids" class="form-select has_select_2 @error('terminal_ids') is-invalid @enderror" 
                                                        multiple
                                                        data-url="{{ route('terminals.select') }}"
                                                        data-placeholder="Select individual terminals">
                                                </select>
                                                @error('terminal_ids')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted">Select specific terminals for assignment</small>
                                            </div>

                                            <!-- All Terminals Warning Alert -->
                                            <div class="col-12 mb-3" id="all_terminals_warning" style="display: none;">
                                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                                    <div class="d-flex">
                                                        <i class="ki-duotone ki-shield-tick fs-2hx text-warning me-4">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        <div class="d-flex flex-column">
                                                            <h4 class="mb-1">Warning: All Terminals Assignment</h4>
                                                            <p class="mb-0" id="all_terminals_warning_text">
                                                                This user will be assigned to <strong>ALL terminals</strong> from <strong>ALL branches</strong> within the merchant <strong>{{ $merchant->name }}</strong>. 
                                                                This includes terminals from all branches and locations.
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ki-duotone ki-check fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Assign Terminals
                                    </button>
                                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                        <i class="ki-duotone ki-cross fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Store branch data for dynamic message updates
    var branches = @json($branches);
    
    // Function to update all terminals warning message
    function updateAllTerminalsWarning() {
        var branchId = $('#branch_id').val();
        var warningText = $('#all_terminals_warning_text');
        
        if (branchId) {
            // Find the selected branch
            var selectedBranch = branches.find(function(branch) {
                return branch.id == branchId;
            });
            
            if (selectedBranch) {
                warningText.html(
                    'This user will be assigned to <strong>ALL terminals</strong> in <strong>' + selectedBranch.name + '</strong> branch within the merchant <strong>{{ $merchant->name }}</strong>. ' +
                    'This includes all terminals from this specific branch.'
                );
            }
        } else {
            // No branch selected - show default message
            warningText.html(
                'This user will be assigned to <strong>ALL terminals</strong> from <strong>ALL branches</strong> within the merchant <strong>{{ $merchant->name }}</strong>. ' +
                'This includes terminals from all branches and locations.'
            );
        }
    }
    // Initialize Select2 for user groups
    $('#user_group_ids').select2({
        ajax: {
            url: '{{ route("user-groups.select") }}',
            type: 'get',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    branch_id: $('#branch_id').val(), 
                    merchant_id: "{{ $merchant->id }}"
                };
            },
            processResults: function (response) {
                console.log('User Groups API Response:', response);
                return {
                    results: response
                };
            },
            cache: true
        },
        placeholder: 'Select user groups',
        allowClear: true,
        minimumResultsForSearch: 1
    });

    // Initialize Select2 for terminal groups
    $('#terminal_group_ids').select2({
        ajax: {
            url: '{{ route("terminal-groups.select") }}',
            type: 'get',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    branch_id: $('#branch_id').val(), 
                    merchant_id: "{{ $merchant->id }}"
                };
            },
            processResults: function (response) {
                console.log('Terminal Groups API Response:', response);
                return {
                    results: response
                };
            },
            cache: true
        },
        placeholder: 'Select terminal groups',
        allowClear: true,
        minimumResultsForSearch: 1
    });

    // Initialize Select2 for individual terminals
    $('#terminal_ids').select2({
        ajax: {
            url: '{{ route("terminals.select") }}',
            type: 'get',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    branch_id: $('#branch_id').val(), 
                    merchant_id: "{{ $merchant->id }}"
                };
            },
            processResults: function (response) {
                console.log('Terminals API Response:', response);
                return {
                    results: response
                };
            },
            cache: true
        },
        placeholder: 'Select individual terminals',
        allowClear: true,
        minimumResultsForSearch: 1
    });

    // Handle assignment type change
    $('input[name="assignment_type"]').on('change', function() {
        var assignmentType = $(this).val();
        
        // Hide all sections first
        $('#user_groups_section').hide();
        $('#terminal_groups_section').hide();
        $('#individual_terminals_section').hide();
        $('#all_terminals_warning').hide();
        
        // Show relevant section based on assignment type
        if (assignmentType === 'user_groups') {
            $('#user_groups_section').show();
            $('#user_group_ids').prop('required', true);
            $('#terminal_group_ids').prop('required', false);
            $('#terminal_ids').prop('required', false);
        } else if (assignmentType === 'terminal_groups') {
            $('#terminal_groups_section').show();
            $('#terminal_group_ids').prop('required', true);
            $('#user_group_ids').prop('required', false);
            $('#terminal_ids').prop('required', false);
        } else if (assignmentType === 'individual_terminals') {
            $('#individual_terminals_section').show();
            $('#terminal_ids').prop('required', true);
            $('#user_group_ids').prop('required', false);
            $('#terminal_group_ids').prop('required', false);
        } else if (assignmentType === 'all_terminals') {
            // Show warning for all terminals assignment
            updateAllTerminalsWarning(); // Update message based on selected branch
            $('#all_terminals_warning').show();
            $('#user_group_ids').prop('required', false);
            $('#terminal_group_ids').prop('required', false);
            $('#terminal_ids').prop('required', false);
        }
    });

    // Handle branch selection change - clear and refresh all selects
    $('#branch_id').on('change', function() {
        var branchId = $(this).val();
        
        if (branchId) {
            // Clear and refresh user groups
            $('#user_group_ids').empty().trigger('change');
            
            // Clear and refresh terminal groups
            $('#terminal_group_ids').empty().trigger('change');
            
            // Clear and refresh individual terminals
            $('#terminal_ids').empty().trigger('change');
        } else {
            // Clear all selects when no branch is selected
            $('#user_group_ids').empty().trigger('change');
            $('#terminal_group_ids').empty().trigger('change');
            $('#terminal_ids').empty().trigger('change');
        }
        
        // Update all terminals warning message if it's currently shown
        if ($('#all_terminals_warning').is(':visible')) {
            updateAllTerminalsWarning();
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        var assignmentType = $('input[name="assignment_type"]:checked').val();
        
        // Validate branch selection
        if (!$('#branch_id').val()) {
            e.preventDefault();
            alert('Please select a branch.');
            $('#branch_id').focus();
            return false;
        }
        
        // Validate based on assignment type
        if (assignmentType === 'user_groups') {
            if (!$('#user_group_ids').val() || $('#user_group_ids').val().length === 0) {
                e.preventDefault();
                alert('Please select at least one user group.');
                $('#user_group_ids').focus();
                return false;
            }
        } else if (assignmentType === 'terminal_groups') {
            if (!$('#terminal_group_ids').val() || $('#terminal_group_ids').val().length === 0) {
                e.preventDefault();
                alert('Please select at least one terminal group.');
                $('#terminal_group_ids').focus();
                return false;
            }
        } else if (assignmentType === 'individual_terminals') {
            if (!$('#terminal_ids').val() || $('#terminal_ids').val().length === 0) {
                e.preventDefault();
                alert('Please select at least one terminal.');
                $('#terminal_ids').focus();
                return false;
            }
        }
    });
});
</script>
@endpush 