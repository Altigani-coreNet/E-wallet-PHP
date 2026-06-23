@extends('layouts.admin.admin_layout')

@section('title', 'Admins Management')

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
        <li class="breadcrumb-item text-muted">{{ __('translation.admins') }}</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.admins_list') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1" id="filter-icon">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.toggle_filters') }}</button>
        <!--end::Menu toggle-->
    </div>
    <!--end::Filter menu-->
    
    <!--begin::Primary button-->
    <a href="{{ route('admins.create') }}" class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Add Admin</a>
    <!--end::Primary button-->
</div>
@endsection


@section('content')
    <style>
        #filters-body {
            transition: all 0.3s ease;
            display: none; /* Hidden by default */
        }
        
        #filter-icon {
            transition: transform 0.3s ease;
            transform: rotate(90deg); /* Rotated by default */
        }
        
        #filters_button:hover {
            transform: translateY(-1px);
        }
        
        #filters_button {
            transition: all 0.3s ease;
        }

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #d1d3e0;
            border-radius: 0.35rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 0.75rem;
            color: #6e707e;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6e707e;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4e73df;
        }

        .select2-dropdown {
            border: 1px solid #d1d3e0;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .select2-search__field {
            border: 1px solid #d1d3e0 !important;
            border-radius: 0.35rem !important;
        }

        .select2-container--default .select2-selection--single:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
    </style>

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <!-- Hidden input for selected IDs -->
            <input type="hidden" id="record-ids" value="">
            
            <!--begin::Filters Card-->
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-body">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">{{ __('translation.filters') }}</h3>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light-primary" id="clear-filters">
                            <i class="ki-duotone ki-refresh fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('translation.clear_filters') }}
                        </button>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body" >
                    <div class="row g-4">
                        <!-- Search -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('translation.search') }}</label>
                            <input type="text" class="form-control" id="search-input" 
                                   placeholder="{{ __('translation.search_by_name_email_phone') }}">
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('translation.status') }}</label>
                            <select class="form-select" id="status-filter">
                                <option value="">{{ __('translation.all_statuses') }}</option>
                                <option value="active">{{ __('translation.active') }}</option>
                                <option value="inactive">{{ __('translation.inactive') }}</option>
                            </select>
                        </div>

                        <!-- Country Filter -->
                        @if(!Auth::guard('admin')->user()->custom_region)
                        <x:select2-input class="col-md-4" name="country" filed-name="country_id" 
                                        url="{{route('countries.select')}}" />
                        @endif
                        
                        <!-- Date Range -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('translation.created_date_from') }}</label>
                            <input type="date" class="form-control" id="date-from">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('translation.created_date_to') }}</label>
                            <input type="date" class="form-control" id="date-to">
                        </div>
                    </div>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Filters Card-->

            <!--begin::Card-->
            <div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Admins Management</h3>
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
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="admins_table">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#admins_table .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-300px">Admin Information</th>
                    <th class="min-w-125px">Roles</th>
                    <th class="min-w-125px">Custom Countries</th>
                    <th class="min-w-200px">Countries</th>
                    <th class="min-w-125px">Status</th>
                    {{-- <th class="min-w-125px">Created At</th> --}}
                    @if(!Auth::guard('admin')->user()->custom_region)
                    <th class="min-w-125px">Country</th>
                    @endif
                    <th class="text-end min-w-100px">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
            </tbody>
        </table>
    </div>
            </div>
            <!--end::Card-->
        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')
<script>
    let search = '', status = '', country = '', dateFrom = '', dateTo = '';

$(document).ready(function() {
    var table = $('#admins_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admins.data") }}',
            type: 'GET',
            data: (q) => {
                q.search = search;
                q.status = status;
                q.country_id = country;
                q.date_from = dateFrom;
                q.date_to = dateTo;
            }
        },
        columns: [
            {
                data: 'record_select',
                name: 'record_select',
                orderable: false,
                searchable: false
            },
            {
                data: 'admin_information',
                name: 'name'
            },
            {
                data: 'roles',
                name: 'roles',
                orderable: false,
                searchable: false
            },
            {
                data: 'custom_region',
                name: 'custom_region',
                orderable: true,
                searchable: false
            },
            {
                data: 'regions',
                name: 'regions',
                orderable: false,
                searchable: false
            },
            {
                data: 'status',
                name: 'status'
            },
            // {
            //     data: 'created_at',
            //     name: 'created_at'
            // },
            @if(!Auth::guard('admin')->user()->custom_region)
            {
                data: 'country',
                name: 'country'
            },
            @endif
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[6, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            url: '{{ asset("admin_assets/datatable-lang/en.json") }}'
        }, drawCallback: function (settings) {
        $('.record_select').prop('checked', false);
        $('#record__select-all').prop('checked', false);
        $('#record-ids').val('');
        $('#bulk-delete').html('{{ __("translation.delete_selected") }}');

        // ✅ Re-initialize KTMenu dropdowns here
        if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
            KTMenu.createInstances();
        }
    }
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
                // Show loading state
                $('#bulk-delete').html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.deleting") }}...');
                
                $.ajax({
                    url: '{{ route("admins.bulk-delete") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: selectedIds
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            toastr.success('{{ __('translation.admins_deleted_successfully') }}');
                            // Reset bulk delete button
                            $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                        } else {
                            toastr.error('{{ __('translation.something_went_wrong') }}');
                            // Reset bulk delete button
                            $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                        }
                    },
                    error: function() {
                        toastr.error('{{ __('translation.something_went_wrong') }}');
                        // Reset bulk delete button
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
    $(document).on('change', '[data-kt-check-target="#admins_table .form-check-input"]', function() {
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

    // Initialize filters state from localStorage
    const isCollapsed = localStorage.getItem('adminFiltersCollapsed');
    const filtersBody = $('#filters-body');
    const filterIcon = $('#filter-icon');
    
    if (isCollapsed === null || isCollapsed === 'true') {
        filtersBody.hide();
        filterIcon.css('transform', 'rotate(90deg)');
        localStorage.setItem('adminFiltersCollapsed', 'true');
    }

    // Filter event handlers
    $('#search-input').on('keyup', function () {
        search = $(this).val();
        table.ajax.reload();
    });

    $('#status-filter').on('change', function () {
        status = $(this).val();
        table.ajax.reload();
    });

    $('#country_id').on('change', function () {
        country = $(this).val();
        table.ajax.reload();
    });

    $('#date-from').on('change', function () {
        dateFrom = $(this).val();
        table.ajax.reload();
    });

    $('#date-to').on('change', function () {
        dateTo = $(this).val();
        table.ajax.reload();
    });

    // Clear filters button
    $('#clear-filters').on('click', function () {
        search = '';
        status = '';
        country = '';
        dateFrom = '';
        dateTo = '';
        
        $('#search-input').val('');
        $('#status-filter').val('');
        $('#country_id').val('').trigger('change');
        $('#date-from').val('');
        $('#date-to').val('');
        
        table.ajax.reload();
    });

    // Toggle filters section
    $('#filters_button').on('click', function () {
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        
        if (filtersBody.is(':visible')) {
            filtersBody.slideUp(300);
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('adminFiltersCollapsed', 'true');
        } else {
            filtersBody.slideDown(300);
            filterIcon.css('transform', 'rotate(0deg)');
            localStorage.setItem('adminFiltersCollapsed', 'false');
        }
    });
});
</script>
@endpush 