{{-- @extends('layouts.admin.admin') --}}
@extends("layouts.admin.admin_layout")
@section('main-head' , __('translation.role_and_permisions'))
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
        <li class="breadcrumb-item text-muted">{{ __('translation.roles') }}</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.roles') }}</li>
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
    <a href='{{ auth()->guard('admin')->check() ? route('admin.roles.create', ['type' => request()->type]) : route('roles.create', ['type' => request()->type])}}'
       class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.add_roles') }}</a>
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
            <!--begin::Filters Card-->
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8">
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
                <div class="card-body" id="filters-body">
                    <div class="row g-4">
                        <!-- Search -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">{{ __('translation.search') }}</label>
                            <input type="text" class="form-control" id="search-input" 
                                   placeholder="{{ __('translation.search_roles') }}">
                        </div>

                        <!-- Country Filter -->
                        @if(!Auth::guard('admin')->user()->custom_region)
                        <x:select2-input class="col-md-3" name="country" filed-name="country_id" 
                                        url="{{route('countries.select')}}" />
                        @endif
                        
                        <!-- Date Range -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">{{ __('translation.created_date_from') }}</label>
                            <input type="date" class="form-control" id="date-from">
                        </div>
                        
                        <div class="col-md-3">
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
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h3 class="fw-bold m-0">{{ __('translation.roles') }}</h3>
                    </div>
                    <!--begin::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-roles-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-roles-table-select="selected_count">0</span>Selected
                            </div>
                            <button type="button" class="btn btn-danger"
                                    data-kt-roles-table-select="delete_selected">Delete Selected
                            </button>
                        </div>
                        <!--end::Group actions-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                    @include('layouts.admin.partials.sessions')
                                <!--begin::Table-->
                    <div id="kt_roles_table_wrapper" class="dt-container dt-bootstrap5 dt-empty-footer w-100">
                        <div class="table-responsive w-100">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 dataTable w-100" id="kt_roles_table" style="width: 100% !important;">
                                <colgroup>
                                    <col data-dt-column="0" >
                                    <col data-dt-column="1" >
                                    <col data-dt-column="2" >
                                    <col data-dt-column="3" >
                                </colgroup>
                                    <thead>
                                    <tr class="text-start text-dark fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-10px pe-2 dt-orderable-none" data-dt-column="0">
                                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_roles_table .form-check-input" value="1">
                                                </div>
                                        </th>
                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc" data-dt-column="1">
                                            <span class="dt-column-title text-dark">{{ __('translation.id') }}</span>
                                        </th>
                                        @if(!Auth::guard('admin')->user()->custom_region)
                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc" data-dt-column="2">
                                            <span class="dt-column-title text-dark">{{ __('translation.country') }}</span>
                                        </th>
                                        @endif
                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc" data-dt-column="2">
                                            <span class="dt-column-title text-dark">{{ __('translation.name') }}</span>
                                        </th>
                                        <th class="min-w-125px dt-orderable-asc dt-orderable-desc" data-dt-column="3">
                                            <span class="dt-column-title text-dark">{{ __('translation.permission_count') }}</span>
                                        </th>
                                        <th class="text-end min-w-70px dt-orderable-none" data-dt-column="4">
                                            <span class="dt-column-title text-dark">{{ __('translation.action') }}</span>
                                        </th>
                                    </tr>
                                    </thead>
                                <tbody class="fw-semibold text-dark">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const typeParam = urlParams.get('type');
        const parentParam = urlParams.get('parent');
        
        let search = '', country = '', dateFrom = '', dateTo = '';

        let rolesTable = $('#kt_roles_table').DataTable({
            dom: "tiplr",
            serverSide: true,
            processing: true,
            autoWidth: false,
            scrollX: true,
            "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            },
            ajax: {
                url: '{{  route("admin.roles.data")  }}',
                data: function (d) {
                    if (typeParam) {
                        d.type = typeParam;
                    }
                    if (parentParam) {
                        d.parent = parentParam;
                    }
                    d.search = search;
                    d.country_id = country;
                    d.date_from = dateFrom;
                    d.date_to = dateTo;
                }
            },
            columns: [{
                data: 'record_select',
                name: 'record_select',
                searchable: false,
                sortable: false,
                width: '1%'
            },
            {
                data: 'id',
                name: 'id',
                // width: '10%'
            },
            @if(!Auth::guard('admin')->user()->custom_region)
            {
                data: 'country',
                name: 'country',
                width: '15%'
            },
            @endif
            {
                data: 'name',
                name: 'name',
                width: '30%'
            },
            {
                data: 'permission_count',
                name: 'permission_count',
                width: '20%'
            },
            {
                data: 'actions',
                name: 'actions',
                searchable: false,
                sortable: false,
                width: '20%',
                className: 'text-end',
                render: function(data, type, row) {
                    return '<div class="text-end">' + data + '</div>';
                }
            }],
            order: [[1, 'desc']],
            drawCallback: function (settings) {
                $('.record__select').prop('checked', false);
                $('#record__select-all').prop('checked', false);
                $('#record-ids').val();
                $('#bulk-delete').attr('disabled', true);
                
                // Force table to maintain full width
                $('#kt_roles_table').css('width', '100%');
                $('#kt_roles_table_wrapper').css('width', '100%');
            }
        });

        $('#data_search').keyup(function () {
            rolesTable.search(this.value).draw();
        });

        // Ensure table maintains full width on window resize
        $(window).on('resize', function () {
            $('#kt_roles_table').css('width', '100%');
            $('#kt_roles_table_wrapper').css('width', '100%');
        });

        // Initialize filters state from localStorage
        $(document).ready(function() {
            const isCollapsed = localStorage.getItem('rolesFiltersCollapsed');
            const filtersBody = $('#filters-body');
            const filterIcon = $('#filter-icon');
            
            if (isCollapsed === null || isCollapsed === 'true') {
                filtersBody.hide();
                filterIcon.css('transform', 'rotate(90deg)');
                localStorage.setItem('rolesFiltersCollapsed', 'true');
            }
        });

        // Filter event handlers
        $('#search-input').on('keyup', function () {
            search = $(this).val();
            rolesTable.ajax.reload();
        });

        $('#country_id').on('change', function () {
            country = $(this).val();
            rolesTable.ajax.reload();
        });

        $('#date-from').on('change', function () {
            dateFrom = $(this).val();
            rolesTable.ajax.reload();
        });

        $('#date-to').on('change', function () {
            dateTo = $(this).val();
            rolesTable.ajax.reload();
        });

        // Clear filters button
        $('#clear-filters').on('click', function () {
            search = '';
            country = '';
            dateFrom = '';
            dateTo = '';
            
            $('#search-input').val('');
            $('#country_id').val('').trigger('change');
            $('#date-from').val('');
            $('#date-to').val('');
            
            rolesTable.ajax.reload();
        });

        // Toggle filters section
        $('#filters_button').on('click', function () {
            const filtersBody = $('#filters-body');
            const filterIcon = $('#filter-icon');
            
            if (filtersBody.is(':visible')) {
                filtersBody.slideUp(300);
                filterIcon.css('transform', 'rotate(90deg)');
                localStorage.setItem('rolesFiltersCollapsed', 'true');
            } else {
                filtersBody.slideDown(300);
                filterIcon.css('transform', 'rotate(0deg)');
                localStorage.setItem('rolesFiltersCollapsed', 'false');
            }
        });
    </script>
@endpush
