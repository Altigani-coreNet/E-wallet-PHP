@extends("layouts.admin.admin_layout")
@section('main-head' , __('translation.terminals_management'))
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
        <li class="breadcrumb-item text-muted">{{ __('translation.terminals') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.filter') }}</button>
        <!--end::Menu toggle-->
        <!--begin::Menu 1-->
       
        <!--end::Menu 1-->
    </div>
    <!--end::Filter menu-->
    <!--begin::Secondary button-->
    <!--end::Secondary button-->    
    <!--begin::Import button-->
    <button type="button" class="btn btn-sm fw-bold btn-success" data-bs-toggle="modal" data-bs-target="#importTerminalsModal">
        <i class="ki-duotone ki-file-up fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.import') }}
    </button>
    <!--end::Import button-->
    <!--begin::Export button-->
    <a href="{{ route('terminals.export', request()->query()) }}" class="btn btn-sm fw-bold btn-info" id="export-terminals">
        <i class="ki-duotone ki-file-down fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.export') }}
    </a>
    <!--end::Export button-->
    <!--begin::Primary button-->
    <a href='{{ route('terminals.create')}}' class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>

        {{ __('translation.add') }}</a>
    <!--end::Primary button-->
</div>
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <div class="row g-5 g-xl-8 mt-4">
            </div>
            <!-- Hidden input for selected IDs -->
            <input type="hidden" id="record-ids" value="">
            
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8 d-none" id="filters_card">
                <!--begin::Body-->
                <div class="card-body">
                    <div class="row">
                        <x:text-input class="col-md-3" name='search'
                                      filedname="search" value="{{old('')}}"/>
                        <x:select-options class="col-md-3" name="status"
                                          filed-name='status'
                                          :options="['active', 'inactive']"
                                          value="{{old('status')}}"/>
                        <x:select-options class="col-md-3" name="terminal_status"
                                          filed-name='terminal_status'
                                          :options="['online', 'offline', 'testing']"
                                          value="{{old('terminal_status')}}"/>

                        <x:select2-input class="col-md-3" name="merchant" filed-name="merchant_id"
                                          url="{{route('merchants.select')}}" />
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">{{ __('translation.from_date') }}</label>
                            <input type="date" class="form-control" id="from_date" name="from_date" value="{{ old('from_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">{{ __('translation.to_date') }}</label>
                            <input type="date" class="form-control" id="to_date" name="to_date" value="{{ old('to_date') }}">
                        </div>
                        
                    </div>
                </div>
                <!--end::Body-->
            </div>

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
                        {{-- <div class="d-flex justify-content-end" data-kt-terminals-table-toolbar="base">
                            <a href='{{ route('terminals.create')}}'
                               class=" btn btn-primary mx-2">
                                <i class="ki-duotone ki-plus fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('translation.add_terminal') }}
                            </a>
                        </div> --}}
                        <!--end::Toolbar-->
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-terminals-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-terminals-table-select="selected_count"></span>{{ __('translation.selected') }}
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
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="terminals-table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#terminals-table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="text-dark">{{ __('translation.id') }}</th>
                                <th class="min-w-200px text-dark">{{ __('translation.terminal_info') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.merchant') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.manufacturer') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.sdk_id') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.sdk_version') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.android_os') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.add_type') }}</th>
                                <th class="text-dark">{{ __('translation.status') }}</th>
                                <th class="text-dark">{{ __('translation.terminal_status') }}</th>
                                <th class="text-dark">{{ __('translation.created_time') }}</th>
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

    <!--begin::Import Terminals Modal-->
    <div class="modal fade" id="importTerminalsModal" tabindex="-1" aria-labelledby="importTerminalsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importTerminalsModalLabel">
                        <i class="ki-duotone ki-file-up fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('translation.import_terminals') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('translation.close') }}"></button>
                </div>
                <form id="importTerminalsForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="import_file" class="form-label">{{ __('translation.select_file') }}</label>
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
                                    <span>{{ __('translation.import_instructions_text') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <a href="{{ route('terminals.export-template') }}" class="btn btn-sm btn-outline-primary">
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
                            <i class="ki-duotone ki-file-up fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('translation.import') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Import Terminals Modal-->
@endsection

@push('scripts')
    <script>
        let search, status, terminal_status, merchant_id, from_date, to_date;
        let terminalsTable = $('#terminals-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("terminals.data")}}',
                data: (q) => {
                    q.search = search;
                    q.status = status;
                    q.terminal_status = terminal_status;
                    q.merchant_id = merchant_id;
                    q.from_date = from_date;
                    q.to_date = to_date;
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
                {
                    data: 'terminal_info'
                    , name: 'terminal_info'
                    , render: function(data, type, row) {
                        if (type === 'display') {
                            return '<div class="d-flex flex-column">' +
                                   '<span class="fw-bold text-dark">' + (row.name || '') + '</span>' +
                                   '<span class="text-muted fs-7">{{ __("translation.id") }}: ' + (row.terminal_id || '') + '</span>' +
                                   '<span class="text-muted fs-7">{{ __("translation.model") }}: ' + (row.model || '') + '</span>' +
                                   '</div>';
                        }
                        return data;
                    }
                },
                {
                    data: 'merchant_name'
                    , name: 'merchant_name'
                },
                {
                    data: 'manufacturer'
                    , name: 'manufacturer'
                },
                {
                    data: 'sdk_id'
                    , name: 'sdk_id'
                },
                {
                    data: 'sdk_version'
                    , name: 'sdk_version'
                },
                {
                    data: 'android_os'
                    , name: 'android_os'
                },
                {
                    data: 'add_type'
                    , name: 'add_type'
                },
                {
                    data: 'status'
                    , name: 'status'
                },
                {
                    data: 'terminal_status'
                    , name: 'terminal_status'
                },
                {
                    data: 'created_at'
                    , name: 'created_at'
                },
                @if(!Auth::guard('admin')->user()->custom_region)
                {
                    data: 'country'
                    , name: 'country'
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
                // $('#bulk-delete').attr('disabled', true);

                // Re-initialize KTMenu dropdowns here
                if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                    KTMenu.createInstances();
                }
            }
        });

        // Load merchants for filter
        $.get('{{ route("merchants.select") }}', function(data) {
            var merchantSelect = $('[name="merchant_id"]');
            merchantSelect.empty();
            merchantSelect.append('<option value="">{{ __("translation.select_merchant") }}</option>');
            data.forEach(function(merchant) {
                merchantSelect.append(new Option(merchant.text, merchant.id));
            });
        });

        // Ensure table maintains full width on window resize
        $(window).on('resize', function () {
            $('#terminals-table').css('width', '100%');
            $('.table-reponsive').css('width', '100%');
        });

        $("[name='search']").attr("placeholder", "{{ __('translation.search_by_name_terminal_id_model_merchant') }}");

        $("[name='search']").on('keyup', function () {
            search = $(this).val();
            terminalsTable.ajax.reload();
        });

        $("[name='status']").on('change', function () {
            status = $(this).val();
            terminalsTable.ajax.reload();
        });

        $("[name='terminal_status']").on('change', function () {
            terminal_status = $(this).val();
            terminalsTable.ajax.reload();
        });

        $("[name='merchant_id']").on('change', function () {
            merchant_id = $(this).val();
            terminalsTable.ajax.reload();
        });

        // Apply filters button
        $('#apply_filters').on('click', function() {
            from_date = $('#from_date').val();
            to_date = $('#to_date').val();
            terminalsTable.ajax.reload();
        });

        // Clear filters button
        $('#clear_filters').on('click', function() {
            $('[name="search"]').val('');
            $('[name="status"]').val('');
            $('[name="terminal_status"]').val('');
            $('[name="merchant_id"]').val('');
            $('#from_date').val('');
            $('#to_date').val('');
            
            search = '';
            status = '';
            terminal_status = '';
            merchant_id = '';
            from_date = '';
            to_date = '';
            
            terminalsTable.ajax.reload();
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
                        url: '{{ route("terminals.bulk-delete") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ids: selectedIds
                        },
                        success: function(response) {
                            if (response.success) {
                                terminalsTable.ajax.reload();
                                toastr.success('{{ __('translation.terminals_deleted_successfully') }}');
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
        $(document).on('change', '.record__select', function() {
            let selectedIds = [];
            $('.record__select:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            $('#record-ids').val(selectedIds.join(','));
            
            if (selectedIds.length > 0) {
                $('[data-kt-terminals-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-terminals-table-select="selected_count"]').text(selectedIds.length);
            } else {
                $('[data-kt-terminals-table-toolbar="selected"]').addClass('d-none');
            }
        });

        // Handle select all checkbox
        $(document).on('change', '[data-kt-check-target="#terminals-table .form-check-input"]', function() {
            let isChecked = $(this).is(':checked');
            $('.record__select').prop('checked', isChecked);
            
            if (isChecked) {
                let allIds = [];
                $('.record__select').each(function() {
                    allIds.push($(this).val());
                });
                $('#record-ids').val(allIds.join(','));
                $('[data-kt-terminals-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-terminals-table-select="selected_count"]').text(allIds.length);
            } else {
                $('#record-ids').val('');
                $('[data-kt-terminals-table-toolbar="selected"]').addClass('d-none');
            }
        });

        // Filter card toggle functionality
        $('#filters_button').on('click', function(e) {
            e.preventDefault();
            $('#filters_card').toggleClass('d-none');
        });

        // Import terminals functionality
        $('#importTerminalsForm').on('submit', function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);
            let submitBtn = $('#importSubmitBtn');
            let originalText = submitBtn.html();
            
            // Show loading state
            submitBtn.html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.importing") }}...');
            
            $.ajax({
                url: '{{ route("terminals.import") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || '{{ __("translation.terminals_imported_successfully") }}');
                        $('#importTerminalsModal').modal('hide');
                        $('#importTerminalsForm')[0].reset();
                        terminalsTable.ajax.reload();
                    } else {
                        toastr.error(response.message || '{{ __("translation.import_failed") }}');
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ __("translation.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                },
                complete: function() {
                    // Reset button state
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Reset form when modal is closed
        $('#importTerminalsModal').on('hidden.bs.modal', function() {
            $('#importTerminalsForm')[0].reset();
        });
    </script>
@endpush 