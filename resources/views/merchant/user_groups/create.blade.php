@extends('layouts.merchant.merchant_layout')

@section('main-head', 'Add User Group')

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
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
        <li class="breadcrumb-item text-muted">Add User Group</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <div class="m-0">
        <a href="{{ route('merchant.user-groups.index') }}" class="btn btn-light-danger btn-sm me-3">
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
            <div id="merchant-user-group-form-root" data-merchant-id="{{ auth()->user()->merchant_id }}">
                <!-- React component will mount here -->
            </div>
        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for branch selection
    $('#branch_id').select2({
        ajax: {
            url: '{{ route("merchant.branches.select") }}',
            type: 'get',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        },
        placeholder: 'Select Branch (Optional)',
        allowClear: true
    });

    // Override the global Select2 initialization for terminal groups
    $('#terminal_group_ids').select2({
        ajax: {
            url: '{{ route("merchant.terminal-groups.select") }}',
            type: 'get',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    is_active: 1
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

    // Override the global Select2 initialization for terminal
    $('#terminal_id').select2({
        ajax: {
            url: '{{ route("merchant.terminals.select") }}',
            type: 'get',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                console.log('Terminal Select - Search Term:', params.term);
                return {
                    search: params.term,
                    is_active: 1
                };
            },
            processResults: function (response) {
                console.log('Terminal API Response:', response);
                return {
                    results: response
                };
            },
            error: function(xhr, status, error) {
                console.error('Terminal API Error:', error);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        placeholder: 'Select terminal',
        allowClear: true,
        minimumResultsForSearch: 1
    });

    // Override the global Select2 initialization for users
    $('#user_ids').select2({
        ajax: {
            url: '{{ route("merchant.users.select") }}',
            type: 'get',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    status: 'active'
                };
            },
            processResults: function (response) {
                console.log('Users API Response:', response);
                return {
                    results: response
                };
            },
            cache: true
        },
        placeholder: 'Select users',
        allowClear: true,
        minimumResultsForSearch: 1
    });

    // Handle terminal mode change
    $('input[name="is_single_terminal"]').on('change', function() {
        var isSingleTerminal = $(this).val() === '1';
        
        if (isSingleTerminal) {
            $('#terminal_groups_section').hide();
            $('#single_terminal_section').show();
            $('#terminal_group_ids').prop('disabled', true);
            $('#terminal_id').prop('disabled', false);
            // Clear terminal groups when switching to single terminal
            $('#terminal_group_ids').empty().trigger('change');
            
            // Ensure terminal select is properly enabled and visible
            console.log('Single terminal mode - Terminal select disabled:', $('#terminal_id').prop('disabled'));
            console.log('Single terminal mode - Terminal section visible:', $('#single_terminal_section').is(':visible'));
        } else {
            $('#terminal_groups_section').show();
            $('#single_terminal_section').hide();
            $('#terminal_group_ids').prop('disabled', false);
            $('#terminal_id').prop('disabled', true);
            // Clear single terminal when switching to terminal groups
            $('#terminal_id').empty().trigger('change');
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        var isSingleTerminal = $('input[name="is_single_terminal"]:checked').val() === '1';
        
        // Validate terminal selection based on mode
        if (isSingleTerminal) {
            // Single terminal mode - validate terminal_id
            if (!$('#terminal_id').val()) {
                e.preventDefault();
                alert('Please select a terminal.');
                $('#terminal_id').focus();
                return false;
            }
        } else {
            // Multiple terminal groups mode - validate terminal_group_ids
            if (!$('#terminal_group_ids').val() || $('#terminal_group_ids').val().length === 0) {
                e.preventDefault();
                alert('Please select at least one terminal group.');
                $('#terminal_group_ids').focus();
                return false;
            }
        }
        
        // Validate other required fields
        if (!$('#user_ids').val() || $('#user_ids').val().length === 0) {
            e.preventDefault();
            alert('Please select at least one user.');
            $('#user_ids').focus();
            return false;
        }
        
        if (!$('#name').val().trim()) {
            e.preventDefault();
            alert('Please enter a name for the user group.');
            $('#name').focus();
            return false;
        }
    });
});
</script>
@endpush 