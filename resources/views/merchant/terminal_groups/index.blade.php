@extends('layouts.merchant.merchant_layout')
@section('main-head', __('translation.my_terminal_groups'))

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
        <li class="breadcrumb-item text-muted">{{ __('translation.my_terminal_groups') }}</li>
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
    <!--begin::Primary button-->
    <a href='{{ route('merchant.terminal-groups.create')}}' class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>

        {{ __('translation.add_terminal_group') }}</a>
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
                        <x:text-input class="col-md-6" name='search'
                                      filedname="search" value="{{old('')}}"/>
                        <x:select-options class="col-md-6" name="status"
                                          filed-name='status'
                                          :options="['active', 'inactive']"
                                          value="{{old('status')}}"/>
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
                        {{-- <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            <a href='{{ route('merchant.terminal-groups.create')}}'
                               class=" btn btn-primary mx-2">
                                <i class="ki-duotone ki-plus fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('translation.add_terminal_group') }}
                            </a>
                        </div> --}}
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
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="terminal-groups-table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#terminal-groups-table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="text-dark">{{ __('translation.id') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.name') }}</th>
                                <th class="text-dark">{{ __('translation.branch') }}</th>
                                <th class="text-dark">{{ __('translation.terminals_count') }}</th>
                                <th class="text-dark">{{ __('translation.status') }}</th>
                                <th class="text-dark">{{ __('translation.created_at') }}</th>
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
@endsection

@push('scripts')
    <script>
        let search, status;
        let terminalGroupsTable = $('#terminal-groups-table').DataTable({
            dom: "tiplr"
            , serverSide: false
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("merchant.terminal-groups.data")}}',
                data: (q) => {
                    q.search = search;
                    q.status = status;
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
                    data: 'name'
                    , name: 'name'
                },
                {
                    data: 'branch_id'
                    , name: 'branch_id'
                },
                {
                    data: 'terminals_count'
                    , name: 'terminals_count'
                },
                {
                    data: 'status'
                    , name: 'status'
                },
                {
                    data: 'created_at'
                    , name: 'created_at'
                },
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
            $('#terminal-groups-table').css('width', '100%');
            $('.table-reponsive').css('width', '100%');
        });

        $("[name='search']").attr("placeholder", "{{ __('translation.search_by_name') }}");

        $("[name='search']").on('keyup', function () {
            search = $(this).val();
            terminalGroupsTable.ajax.reload();
        });

        $("[name='status']").on('change', function () {
            status = $(this).val();
            terminalGroupsTable.ajax.reload();
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
                        url: '{{ route("merchant.terminal-groups.bulk-delete") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ids: selectedIds
                        },
                        success: function(response) {
                            if (response.success) {
                                terminalGroupsTable.ajax.reload();
                                toastr.success('{{ __('translation.terminal_groups_deleted_successfully') }}');
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
                $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-customer-table-select="selected_count"]').text(selectedIds.length);
            } else {
                $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
            }
        });

        // Handle select all checkbox
        $(document).on('change', '[data-kt-check-target="#terminal-groups-table .form-check-input"]', function() {
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

        // Filter card toggle functionality
        $('#filters_button').on('click', function(e) {
            e.preventDefault();
            $('#filters_card').toggleClass('d-none');
        });
    </script>
@endpush 