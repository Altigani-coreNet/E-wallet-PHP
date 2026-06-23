@extends("layouts.admin.admin_layout")
@section('main-head' , __('translation.users_managements'))
@section('breadcrumb')
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
        <li class="breadcrumb-item text-muted">{{ __('translation.users') }}</li>
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
    
    <!--begin::Import button-->
    <button type="button" class="btn btn-sm fw-bold btn-success" data-bs-toggle="modal" data-bs-target="#importUsersModal">
        <i class="ki-duotone ki-file-up fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.import_users') }}
    </button>
    <!--end::Import button-->
    
    <!--begin::Export button-->
    <button type="button" class="btn btn-sm fw-bold btn-info" id="export-users">
        <i class="ki-duotone ki-download fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.export_users') }}
    </button>
    <!--end::Export button-->
    
    <!--begin::Primary button-->
    <a href='{{ route('users.create')}}' class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.add_user') }}</a>
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
            <div class="row g-5 g-xl-8 mt-4">
            </div>
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
                        <div class="col-md-3">
                            <label class="form-label fw-bold">{{ __('translation.search') }}</label>
                            <input type="text" class="form-control" id="search-input" 
                                   placeholder="{{ __('translation.search_by_name_email_phone') }}">
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">{{ __('translation.status') }}</label>
                            <select class="form-select" id="status-filter">
                                <option value="">{{ __('translation.all_statuses') }}</option>
                                <option value="active">{{ __('translation.active') }}</option>
                                <option value="inactive">{{ __('translation.inactive') }}</option>
                            </select>
                        </div>
                        
                        <x:select2-input class="col-md-3" name="merchant" filed-name="merchant_id"
                        url="{{route('merchants.select')}}" />

                        <x:select2-input class="col-md-3" name="branch" filed-name="branch_id"
                                            url="{{route('branches.select')}}" />

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
                    </div>
                    <!--begin::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <!--end::Toolbar-->
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-customer-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-customer-table-select="selected_count"></span>{{ __('translation.selected') }}
                            </div>
                            <button type="button" class="btn btn-danger" id="bulk-delete">
                                {{ __('translation.delete_selected') }}
                            </button>
                        </div>
                        <!--end::Group actions-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <div class="table-reponsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="users-table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#users-table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="text-dark">{{ __('translation.id') }}</th>
                               
                                {{-- <th class="min-w-125px text-dark">{{ __('translation.image') }}</th> --}}
                                <th class="min-w-200px text-dark">{{ __('translation.user_info') }}</th>
                                {{-- <th class="text-dark">{{ __('translation.email') }}</th> --}}
                                <th class="text-dark">{{ __('translation.merchant') }}</th>
                                <th class="text-dark">{{ __('translation.branch') }}</th>
                                <th class="text-dark">{{ __('translation.roles') }}</th>
                                <th class="text-dark">{{ __('translation.status') }}</th>
                                @if(!Auth::guard('admin')->user()->custom_region)
                                <th class="text-dark">{{ __('translation.country') }}</th>
                                @endif
                                <th class="text-end text-dark">{{ __('translation.actions') }}</th>
                            </tr>
                            <!--end::Table row-->
                            </thead>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <!--end::Table body-->
                        </table>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Container-->
    </div>

    <!--begin::Import Users Modal-->
    <div class="modal fade" id="importUsersModal" tabindex="-1" aria-labelledby="importUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importUsersModalLabel">
                        <i class="ki-duotone ki-file-up fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('translation.import_users') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importUsersForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <x:select2-input class="col-md-12 mb-3" name="merchant" filed-name="import_merchant_id"
                        url="{{route('merchants.select')}}" />
                        
                        <div class="mb-3">
                            <label for="import_file" class="form-label fw-bold">{{ __('translation.select_file') }} <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="import_file" name="import_file" accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">{{ __('translation.supported_formats') }}: .xlsx, .xls, .csv</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <div class="d-flex">
                                <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <div class="d-flex flex-column">
                                    <h5 class="mb-1">{{ __('translation.import_instructions') }}</h5>
                                    <span>All users will be assigned to the selected merchant. Passwords will be auto-generated and sent via email. Duplicate emails or phone numbers will be skipped.</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <a href="{{ route('users.export-template') }}" class="btn btn-sm btn-outline-primary">
                                <i class="ki-duotone ki-download fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('translation.download_template') }}
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('translation.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary" id="importSubmitBtn">
                            <i class="ki-duotone ki-eye fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            Preview Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Import Users Modal-->

    <!--begin::Preview Import Modal-->
    <div class="modal fade" id="previewUsersModal" tabindex="-1" aria-labelledby="previewUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewUsersModalLabel">
                        <i class="ki-duotone ki-eye fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        Preview Import Data
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <h5 class="mb-1">Review Before Import</h5>
                            <span>Please review the data below. Rows with errors or duplicates will be highlighted and skipped during import. Passwords will be auto-generated and sent via email.</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Selected Merchant:</strong> <span id="preview_user_merchant_name" class="text-primary"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Total Rows:</strong> <span id="preview_user_total_rows" class="badge badge-primary"></span>
                            <strong class="ms-3">Valid:</strong> <span id="preview_user_valid_rows" class="badge badge-success"></span>
                            <strong class="ms-3">Invalid:</strong> <span id="preview_user_invalid_rows" class="badge badge-danger"></span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm" id="preview_users_table">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Password</th>
                                    <th>Status</th>
                                    <th>Validation</th>
                                </tr>
                            </thead>
                            <tbody id="preview_users_table_body">
                                <!-- Data will be populated here -->
                            </tbody>
                        </table>
                    </div>

                    <div id="preview_user_errors" class="alert alert-warning mt-3" style="display: none;">
                        <h5><i class="ki-duotone ki-information fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> Issues Found:</h5>
                        <ul id="preview_user_errors_list"></ul>
                        <p class="mb-0"><strong>Note:</strong> Rows with issues will be skipped during import. Only valid rows will be imported.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmUserImportBtn">
                        <i class="ki-duotone ki-check fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Confirm Import
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--end::Preview Import Modal-->
@endsection

@push('scripts')
    <script>
        let search = '', status = '', merchant = '', branch = '', country = '', dateFrom = '', dateTo = '';

        let usersTable = $('#users-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("user.data")}}',
                data: (q) => {
                    q.search = search;
                    q.status = status;
                    q.merchant = merchant;
                    q.branch = branch;
                    q.country_id = country;
                    q.date_from = dateFrom;
                    q.date_to = dateTo;
                }
            }
            , columns: [{
                data: 'record_select'
                , name: 'record_select'
                , searchable: false
                , sortable: false
                , width: '1%'
            },
                {
                    data: 'id'
                    , name: 'id'
                },
                
                // {
                //     data: 'profile_image'
                //     , name: 'profile_image'
                //     , searchable: false
                //     , sortable: false
                // },
                {
                    data: 'user_info'
                    , name: 'user_info'
                    , searchable: false
                    , sortable: false
                },
                // {
                //     data: 'email'
                //     , name: 'email'
                // },
                {
                    data: 'merchant_id'
                    , name: 'merchant_id'
                },
                {
                    data: 'branch_id'
                    , name: 'branch_id'
                },
                {
                    data: 'roles'
                    , name: 'roles'
                },
                {
                    data: 'status'
                    , name: 'status'
                },
                @if(!Auth::guard('admin')->user()->custom_region)
                {
                    data: 'country'
                    , name: 'country',
                    orderable: false,
                    searchable: false
                },
                @endif
                {
                    data: 'actions'
                    , name: 'actions'
                    , searchable: false
                    , sortable: false
                    , width: '20%'
                }
            ]
            , order: [
                [1, 'desc']
            ]
            , drawCallback: function (settings) {
            $('.record__select').prop('checked', false);
            $('#record__select-all').prop('checked', false);
            $('#record-ids').val('');

                // Re-initialize KTMenu dropdowns here
            if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                KTMenu.createInstances();
            }
        }
        });

        // Ensure table maintains full width on window resize
        $(window).on('resize', function () {
            $('#users-table').css('width', '100%');
            $('.table-reponsive').css('width', '100%');
        });

        // Load merchants for filter
        function loadMerchants() {
            $('#merchant-filter').select2({
                ajax: {
                    url: '{{ route("merchants.select") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name
                                };
                            }),
                            pagination: {
                                more: false
                            }
                        };
                    },
                    cache: true
                },
                placeholder: '{{ __("translation.select_merchant") }}',
                allowClear: true,
                minimumInputLength: 0,
                width: '100%'
            });
        }

        // Load branches for filter
        function loadBranches(merchantId = '') {
            $('#branch-filter').select2({
                ajax: {
                    url: '{{ route("branches.select") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            merchant_id: merchantId,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name
                                };
                            }),
                            pagination: {
                                more: false
                            }
                        };
                    },
                    cache: true
                },
                placeholder: '{{ __("translation.select_branch") }}',
                allowClear: true,
                minimumInputLength: 0,
                width: '100%'
            });
        }

        // Initialize filters on page load
        $(document).ready(function() {
            loadMerchants();
            loadBranches();
            
            const isCollapsed = localStorage.getItem('userFiltersCollapsed');
            const filtersBody = $('#filters-body');
            const filterIcon = $('#filter-icon');
            
            if (isCollapsed === null || isCollapsed === 'true') {
                filtersBody.hide();
                filterIcon.css('transform', 'rotate(90deg)');
                localStorage.setItem('userFiltersCollapsed', 'true');
            }
        });

        // Filter event handlers
        $('#search-input').on('keyup', function () {
            search = $(this).val();
            usersTable.ajax.reload();
        });

        $('#country_id').on('change', function () {
            country = $(this).val();
            usersTable.ajax.reload();
        });

        $('#status-filter').on('change', function () {
            status = $(this).val();
            usersTable.ajax.reload();
        });

        $('#merchant-filter').on('change', function () {
            merchant = $(this).val();
            // Reload branches when merchant changes
            if (merchant) {
                loadBranches(merchant);
            } else {
                loadBranches(); // Reset branches to show all
            }
            usersTable.ajax.reload();
        });

        $('#branch-filter').on('change', function () {
            branch = $(this).val();
            usersTable.ajax.reload();
        });

        @if(!Auth::guard('admin')->user()->custom_region)
        $('#country-filter').on('change', function () {
            country = $(this).val();
            usersTable.ajax.reload();
        });
        @endif

        $('#date-from').on('change', function () {
            dateFrom = $(this).val();
            usersTable.ajax.reload();
        });

        $('#date-to').on('change', function () {
            dateTo = $(this).val();
            usersTable.ajax.reload();
        });

        // Clear filters button
        $('#clear-filters').on('click', function () {
            search = '';
            status = '';
            merchant = '';
            branch = '';
            country = '';
            dateFrom = '';
            dateTo = '';
            
            $('#search-input').val('');
            $('#status-filter').val('');
            $('#merchant-filter').val('').trigger('change');
            $('#branch-filter').val('').trigger('change');
            @if(!Auth::guard('admin')->user()->custom_region)
            $('#country-filter').val('').trigger('change');
            @endif
            $('#date-from').val('');
            $('#date-to').val('');
            
            // Reset Select2 dropdowns
            $('#merchant-filter').select2('val', '');
            $('#branch-filter').select2('val', '');
            
            usersTable.ajax.reload();
        });

        // Export users
        $('#export-users').on('click', function () {
            let params = new URLSearchParams({
                search: search,
                status: status,
                merchant: merchant,
                branch: branch,
                date_from: dateFrom,
                date_to: dateTo
            });
            
            window.open('{{ route("users.export") }}?' + params.toString(), '_blank');
        });

        // Toggle filters section
        $('#filters_button').on('click', function () {
            const filtersBody = $('#filters-body');
            const filterIcon = $('#filter-icon');
            
            if (filtersBody.is(':visible')) {
                filtersBody.slideUp(300);
                filterIcon.css('transform', 'rotate(90deg)');
                localStorage.setItem('userFiltersCollapsed', 'true');
            } else {
                filtersBody.slideDown(300);
                filterIcon.css('transform', 'rotate(0deg)');
                localStorage.setItem('userFiltersCollapsed', 'false');
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
                    $('#bulk-delete').html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.deleting") }}...');
                    
                    $.ajax({
                        url: '{{ route("users.bulk-delete") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ids: selectedIds
                        },
                        success: function(response) {
                            if (response.success) {
                                usersTable.ajax.reload();
                                toastr.success('{{ __('translation.users_deleted_successfully') }}');
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
        $(document).on('change', '.record__select', function() {
            let selectedIds = [];
            $('.record__select:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            $('#record-ids').val(selectedIds.join(','));
            
            if (selectedIds.length > 0) {
                $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-customer-table-select="selected_count"]').text(selectedIds.length);
            } else {
                $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
            }
        });

        // Handle select all checkbox
        $(document).on('change', '[data-kt-check-target="#users-table .form-check-input"]', function() {
            let isChecked = $(this).is(':checked');
            $('.record__select').prop('checked', isChecked);
            
            if (isChecked) {
                let allIds = [];
                $('.record__select').each(function() {
                    allIds.push($(this).val());
                });
                $('#record-ids').val(allIds.join(','));
                $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-customer-table-select="selected_count"]').text(allIds.length);
            } else {
                $('#record-ids').val('');
                $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
            }
        });

        // Store preview data globally
        let previewUsersData = null;
        let previewUsersMerchantId = null;
        let previewUsersFile = null;

        // Import users functionality - Preview first
        $('#importUsersForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate merchant selection
            let merchantId = $('#import_merchant_id').val();
            if (!merchantId) {
                toastr.warning('Please select a merchant');
                return;
            }
            
            // Validate file selection
            let fileInput = $('#import_file')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                toastr.warning('Please select a file to import');
                return;
            }
            
            let formData = new FormData(this);
            let submitBtn = $('#importSubmitBtn');
            let originalText = submitBtn.html();
            formData.append('merchant_id', merchantId);
            submitBtn.prop('disabled', true).html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> Loading Preview...');
            
            // Call preview endpoint
            $.ajax({
                url: '{{ route("users.import-preview") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Store data for later import
                        previewUsersData = response.data;
                        previewUsersMerchantId = merchantId;
                        previewUsersFile = fileInput.files[0];
                        
                        // Get merchant name
                        let merchantName = $('#import_merchant_id option:selected').text();
                        
                        // Count valid and invalid rows
                        let validRows = response.data.filter(row => row.is_valid).length;
                        let invalidRows = response.data.length - validRows;
                        
                        // Populate preview modal
                        $('#preview_user_merchant_name').text(merchantName);
                        $('#preview_user_total_rows').text(response.data.length);
                        $('#preview_user_valid_rows').text(validRows);
                        $('#preview_user_invalid_rows').text(invalidRows);
                        
                        // Build table rows
                        let tableBody = $('#preview_users_table_body');
                        tableBody.empty();
                        
                        response.data.forEach(function(row, index) {
                            let rowClass = row.is_valid ? '' : 'table-danger';
                            let validationBadge = row.is_valid 
                                ? '<span class="badge badge-success">Valid</span>' 
                                : '<span class="badge badge-danger">Invalid</span>';
                            
                            let statusBadge = row.is_active 
                                ? '<span class="badge badge-success">Active</span>' 
                                : '<span class="badge badge-secondary">Inactive</span>';
                            
                            tableBody.append(`
                                <tr class="${rowClass}">
                                    <td>${index + 1}</td>
                                    <td>${row.name || '<span class="text-muted">-</span>'}</td>
                                    <td>${row.email || '<span class="text-muted">-</span>'}</td>
                                    <td>${row.phone || '<span class="text-muted">-</span>'}</td>
                                    <td><span class="badge badge-info">${row.password || 'Auto-generated'}</span></td>
                                    <td>${statusBadge}</td>
                                    <td>${validationBadge}${row.errors ? '<br><small class="text-danger">' + row.errors + '</small>' : ''}</td>
                                </tr>
                            `);
                        });
                        
                        // Show validation errors if any
                        if (response.errors && response.errors.length > 0) {
                            let errorsList = $('#preview_user_errors_list');
                            errorsList.empty();
                            response.errors.forEach(function(error) {
                                errorsList.append(`<li>${error}</li>`);
                            });
                            $('#preview_user_errors').show();
                        } else {
                            $('#preview_user_errors').hide();
                        }
                        
                        // Hide import modal and show preview modal
                        $('#importUsersModal').modal('hide');
                        $('#previewUsersModal').modal('show');
                    } else {
                        toastr.error(response.message || 'Failed to preview data');
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ __("translation.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join('<br>');
                    }
                    toastr.error(errorMessage);
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Confirm import functionality
        $('#confirmUserImportBtn').on('click', function() {
            if (!previewUsersData || !previewUsersMerchantId) {
                toastr.error('No preview data available');
                return;
            }
            
            let confirmBtn = $(this);
            let originalText = confirmBtn.html();
            
            // Show loading state
            confirmBtn.prop('disabled', true).html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.importing") }}...');
            
            // Create form data with the original file
            let formData = new FormData();
            formData.append('import_file', previewUsersFile);
            formData.append('merchant_id', previewUsersMerchantId);
            formData.append('_token', '{{ csrf_token() }}');
            
            $.ajax({
                url: '{{ route("users.import") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        let message = response.message || '{{ __("translation.users_imported_successfully") }}';
                        if (response.skipped_count && response.skipped_count > 0) {
                            message += ` (${response.imported_count} imported, ${response.skipped_count} skipped due to duplicates or errors)`;
                        }
                        toastr.success(message);
                        $('#previewUsersModal').modal('hide');
                        $('#importUsersForm')[0].reset();
                        $('#import_merchant_id').val('').trigger('change');
                        usersTable.ajax.reload();
                        
                        // Clear preview data
                        previewUsersData = null;
                        previewUsersMerchantId = null;
                        previewUsersFile = null;
                    } else {
                        toastr.error(response.message || '{{ __("translation.import_failed") }}');
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ __("translation.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join('<br>');
                    }
                    toastr.error(errorMessage);
                },
                complete: function() {
                    confirmBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Reset form when modal is closed
        $('#importUsersModal').on('hidden.bs.modal', function() {
            $('#importUsersForm')[0].reset();
            $('#import_merchant_id').val('').trigger('change');
        });
    </script>
@endpush

@push("scripts")
    <script>
        // Handle merchant selection and update branch dropdown
        $('#merchant_id').on('change', function () {
            var merchantId = $(this).val();
            var branchSelect = $('#branch_id');

            // Clear and disable branch dropdown
            branchSelect.empty().append('<option value="">Select Branch</option>').prop('disabled', true);

            if (merchantId) {
                // Enable branch dropdown and load branches for selected merchant
                branchSelect.prop('disabled', false);
                
                branchSelect.select2({
                    ajax: {
                        url: '{{route("branches.select")}}',
                        type: 'get',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                search: params.term,
                                merchant_id: merchantId,
                            };
                        },
                        processResults: function (response) {
                            return {
                                results: response
                            };
                        },
                        cache: true
                    }
                });
            }
        });

        // Initialize branch dropdown as disabled
        $(document).ready(function() {
            $('#branch_id').prop('disabled', true);
        });
    </script>
@endpush