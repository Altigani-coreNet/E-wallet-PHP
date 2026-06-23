@extends('layouts.admin.admin_layout')

@section('title', 'Countries Management')

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1" >
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
        <li class="breadcrumb-item text-muted">{{ __('translation.countries') }}</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.countries_list') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<a href="{{ route('admin.countries.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus"></i> Add New Country
</a>
@endsection

@section('content')
<!-- Hidden input for selected IDs -->
<input type="hidden" id="record-ids" value="">

<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Countries Management</h3>
        </div>
        <div class="card-toolbar">
            <!--begin::Toolbar-->
            <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                
            </div>
            <!--end::Toolbar-->
            <!--begin::Group actions-->
            <div class="d-flex justify-content-end align-items-center d-none"
                 data-kt-user-table-toolbar="selected">
                <div class="fw-bolder me-5">
                    <span class="me-2" data-kt-user-table-select="selected_count"></span>{{ __('translation.selected') }}
                </div>
                <button type="button" class="btn btn-danger" id="bulk-delete">
                    {{ __('translation.delete_selected') }}
                </button>
            </div>
            <!--end::Group actions-->
        </div>
    </div>
    <div class="card-body py-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="countries_table">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#countries_table .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">ID</th>
                    <th class="min-w-125px">Name (English)</th>
                    <th class="min-w-125px">Name (Arabic)</th>
                    <th class="min-w-125px">Short Name</th>
                    <th class="min-w-125px">Code</th>
                    <th class="min-w-100px">Status</th>
                    <th class="min-w-100px">Created At</th>
                    <th class="text-end min-w-100px">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#countries_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.countries.data") }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'record_select',
                name: 'record_select',
                orderable: false,
                searchable: false
            },
            {
                data: 'id',
                name: 'id'
            },
            {
                data: 'name_en',
                name: 'name_en'
            },
            {
                data: 'name_ar',
                name: 'name_ar'
            },
            {
                data: 'short_name',
                name: 'short_name'
            },
            {
                data: 'code',
                name: 'code'
            },
            {
                data: 'status',
                name: 'status'
            },
            {
                data: 'created_at',
                name: 'created_at'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            url: '{{ asset("admin_assets/datatable-lang/" . app()->getLocale() . ".json") }}'
        },
        drawCallback: function (settings) {
            $('.record_select').prop('checked', false);
            $('[data-kt-check-target="#countries_table .form-check-input"]').prop('checked', false);
            $('#record-ids').val('');
            
            // Re-initialize KTMenu dropdowns
            if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                KTMenu.createInstances();
            }
            
            // Re-initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Handle status toggle change
    $(document).on('change', '.status-toggle', function() {
        var toggle = $(this);
        var countryId = toggle.data('id');
        var isChecked = toggle.is(':checked');
        
        $.ajax({
            url: '{{ route("admin.countries.show", ":id") }}'.replace(':id', countryId),
            method: 'GET',
            data: {
                status: true
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error('Failed to update status');
                    toggle.prop('checked', !isChecked);
                }
            },
            error: function(xhr) {
                toastr.error('{{ __('translation.something_went_wrong') }}');
                toggle.prop('checked', !isChecked);
            }
        });
    });

    // Handle bulk delete
    $(document).on('click', '#bulk-delete', function() {
        let selectedIds = $('#record-ids').val();
        
        if (!selectedIds) {
            toastr.warning('{{ __('translation.please_select_records') }}');
            return;
        }
        
        Swal.fire({
            title: '{{ __('translation.are_you_sure') }}',
            text: "{{ __('translation.you_wont_be_able_to_revert') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ __('translation.yes_delete_it') }}',
            cancelButtonText: '{{ __('translation.cancel') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#bulk-delete').html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.deleting") }}...');
                
                $.ajax({
                    url: '{{ route("admin.countries.bulk-delete") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: selectedIds
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            toastr.success('Countries deleted successfully');
                            $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                        } else {
                            toastr.error('{{ __('translation.something_went_wrong') }}');
                            $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                        }
                    },
                    error: function() {
                        toastr.error('{{ __('translation.something_went_wrong') }}');
                        $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                    }
                });
            }
        });
    });

    // Handle checkbox selection
    $(document).on('change', '.record_select', function() {
        let selectedIds = [];
        $('.record_select:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        $('#record-ids').val(selectedIds.join(','));
        
        if (selectedIds.length > 0) {
            $('[data-kt-user-table-toolbar="selected"]').removeClass('d-none');
            $('[data-kt-user-table-select="selected_count"]').text(selectedIds.length);
        } else {
            $('[data-kt-user-table-toolbar="selected"]').addClass('d-none');
        }
    });

    // Handle select all checkbox
    $(document).on('change', '[data-kt-check-target="#countries_table .form-check-input"]', function() {
        let isChecked = $(this).is(':checked');
        $('.record_select').prop('checked', isChecked);
        
        if (isChecked) {
            let allIds = [];
            $('.record_select').each(function() {
                allIds.push($(this).val());
            });
            $('#record-ids').val(allIds.join(','));
            $('[data-kt-user-table-toolbar="selected"]').removeClass('d-none');
            $('[data-kt-user-table-select="selected_count"]').text(allIds.length);
        } else {
            $('#record-ids').val('');
            $('[data-kt-user-table-toolbar="selected"]').addClass('d-none');
        }
    });
});
</script>
@endpush
